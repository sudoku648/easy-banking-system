<?php

declare(strict_types=1);

namespace App\Tests\Functional\Transaction\Application\Command;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Provider\ClockInterface;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Shared\ApplicationTestCase;
use App\Tests\Support\Event\InMemoryEventBus;
use App\Tests\Support\Provider\MockClock;
use App\Tests\Support\Provider\MockExchangeRateProvider;
use App\Transaction\Application\Command\WithdrawCashFromAtmCommand;
use App\Transaction\Application\Command\WithdrawCashFromAtmCommandHandler;
use App\Transaction\Domain\Event\CashWithdrawnFromAtm;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\BankAccountId as TransactionBankAccountId;
use App\Transaction\Domain\ValueObject\TransactionType;

final class WithdrawCashFromAtmTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private DebitCardRepositoryInterface $debitCardRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private ExchangeRateProviderInterface $exchangeRateProvider;
    private EventBus $eventBus;
    private MockClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->debitCardRepository = self::getContainer()->get(DebitCardRepositoryInterface::class);
        $this->transactionRepository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->exchangeRateProvider = self::getContainer()->get(ExchangeRateProviderInterface::class);
        $this->eventBus = self::getContainer()->get(EventBus::class);
        $this->clock = self::getContainer()->get(ClockInterface::class);

        // Setup default exchange rates for MockExchangeRateProvider
        if ($this->exchangeRateProvider instanceof MockExchangeRateProvider) {
            $this->exchangeRateProvider->setRate(Currency::PLN, Currency::EUR, 0.23);
            $this->exchangeRateProvider->setRate(Currency::EUR, Currency::PLN, 4.35);
        }
    }

    public function testWithdrawCashFromAtm(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Withdraw money using card
        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 50000, // 500.00 PLN
            currency: 'PLN',
        );

        $handler($command);

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(50000, $accountAfter->balance->getAmount());
    }

    public function testWithdrawCashFromAtmCreatesTransaction(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 30000,
            currency: 'PLN',
        );

        $handler($command);

        // Verify transaction
        $transactions = $this->transactionRepository->findByBankAccountId(
            TransactionBankAccountId::fromString($account->id->getValue()),
        );

        self::assertCount(1, $transactions);

        $transaction = $transactions[0];
        self::assertSame(TransactionType::ATM_WITHDRAWAL, $transaction->type);
        self::assertSame(30000, $transaction->amount->getAmount());
        self::assertSame(Currency::PLN, $transaction->amount->getCurrency());
        self::assertSame(30000, $transaction->originalAmount->getAmount());
    }

    public function testWithdrawCashFromAtmDispatchesCashWithdrawnFromAtmEvent(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        /** @var InMemoryEventBus $eventBus */
        $eventBus = $this->eventBus;
        $eventBus->clear();

        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 25000,
            currency: 'PLN',
        );

        $handler($command);

        $events = $eventBus->getDispatchedEventsOfType(CashWithdrawnFromAtm::class);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertSame(25000, $event->amount->getAmount());
        self::assertTrue($event->iban->equals($account->iban));
    }

    public function testWithdrawCashFromAtmThrowsExceptionForNonExistentCard(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $command = new WithdrawCashFromAtmCommand(
            cardNumber: '1234567890123456',
            amount: 10000,
            currency: 'PLN',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Debit card not found');

        $handler($command);
    }

    public function testWithdrawCashFromAtmThrowsExceptionForInsufficientFunds(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add small balance
        $account->deposit(new Money(10000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Try to withdraw more than available
        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 50000,
            currency: 'PLN',
        );

        $this->expectException(InsufficientFundsException::class);

        $handler($command);
    }

    public function testWithdrawCashFromAtmFromEurAccount(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'EUR');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(50000, Currency::EUR));
        $this->bankAccountRepository->save($account);

        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 20000, // 200.00 EUR
            currency: 'EUR',
        );

        $handler($command);

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(30000, $accountAfter->balance->getAmount());
        self::assertSame(Currency::EUR, $accountAfter->balance->getCurrency());
    }

    public function testMultipleWithdrawalsDecrementBalance(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // First withdrawal
        $handler(new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 10000,
            currency: 'PLN',
        ));

        // Second withdrawal
        $handler(new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 5000,
            currency: 'PLN',
        ));

        // Third withdrawal
        $handler(new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 3000,
            currency: 'PLN',
        ));

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(82000, $accountAfter->balance->getAmount());

        // Verify transactions
        $transactions = $this->transactionRepository->findByBankAccountId(
            TransactionBankAccountId::fromString($account->id->getValue()),
        );

        self::assertCount(3, $transactions);
    }

    public function testWithdrawCashFromAtmThrowsExceptionForBlockedCard(): void
    {
        $handler = $this->createWithdrawCashFromAtmHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue and block debit card
        $debitCard = $this->issueDebitCard($account->id);
        $debitCard->block();
        $this->debitCardRepository->save($debitCard);

        // Add balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Try to withdraw with blocked card
        $command = new WithdrawCashFromAtmCommand(
            cardNumber: $debitCard->cardNumber->getValue(),
            amount: 10000,
            currency: 'PLN',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Debit card is blocked or inactive');

        $handler($command);
    }

    private function createWithdrawCashFromAtmHandler(): WithdrawCashFromAtmCommandHandler
    {
        return new WithdrawCashFromAtmCommandHandler(
            $this->transactionRepository,
            $this->eventBus,
            $this->bankAccountRepository,
            $this->debitCardRepository,
            $this->exchangeRateProvider,
            $this->clock,
        );
    }

    private function openBankAccount(CustomerId $customerId, string $currency): void
    {
        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $command = new OpenBankAccountCommand($customerId->getValue(), $currency);
        $handler($command);
    }

    private function issueDebitCard(BankAccountId $bankAccountId): DebitCard
    {
        $cardNumber = $this->debitCardRepository->generateCardNumber();
        $debitCard = DebitCard::issue(
            $this->debitCardRepository->nextIdentity(),
            $cardNumber,
            $bankAccountId,
        );

        $this->debitCardRepository->save($debitCard);

        return $debitCard;
    }
}
