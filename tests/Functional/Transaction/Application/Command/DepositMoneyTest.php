<?php

declare(strict_types=1);

namespace App\Tests\Functional\Transaction\Application\Command;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Tests\Shared\ApplicationTestCase;
use App\Tests\Support\Event\InMemoryEventBus;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Application\Command\DepositMoneyCommandHandler;
use App\Transaction\Domain\Event\MoneyDeposited;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionType;

final class DepositMoneyTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->transactionRepository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->eventBus = self::getContainer()->get(EventBus::class);
    }

    public function testDepositMoneyIntoAccount(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Initial balance should be 0
        self::assertSame(0, $account->balance->getAmount());

        // Deposit money
        $command = new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 50000, // 500.00 PLN
            currency: 'PLN',
        );

        $handler($command);

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(50000, $accountAfter->balance->getAmount());
    }

    public function testDepositMoneyCreatesTransaction(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        $command = new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 30000,
            currency: 'PLN',
        );

        $handler($command);

        // Verify transaction
        $transactions = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );

        self::assertCount(1, $transactions);

        $transaction = $transactions[0];
        self::assertSame(TransactionType::CASH_DEPOSIT, $transaction->type);
        self::assertSame(30000, $transaction->amount->getAmount());
        self::assertSame(Currency::PLN, $transaction->amount->getCurrency());
        self::assertSame(30000, $transaction->originalAmount->getAmount());
    }

    public function testDepositMoneyDispatchesMoneyDepositedEvent(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        /** @var InMemoryEventBus $eventBus */
        $eventBus = $this->eventBus;
        $eventBus->clear();

        $command = new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 25000,
            currency: 'PLN',
        );

        $handler($command);

        $events = $eventBus->getDispatchedEventsOfType(MoneyDeposited::class);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertSame(25000, $event->amount->getAmount());
        self::assertTrue($event->iban->equals($account->iban));
    }

    public function testDepositMoneyThrowsExceptionForNonExistentAccount(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $nonExistentId = CustomerId::generate()->getValue();

        $command = new DepositMoneyCommand(
            bankAccountId: $nonExistentId,
            amount: 10000,
            currency: 'PLN',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Bank account not found');

        $handler($command);
    }

    public function testDepositMoneyThrowsExceptionForCurrencyMismatch(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Try to deposit EUR into PLN account
        $command = new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 10000,
            currency: 'EUR',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Deposit currency must match account currency');

        $handler($command);
    }

    public function testDepositMoneyIntoEurAccount(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'EUR');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        $command = new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 20000, // 200.00 EUR
            currency: 'EUR',
        );

        $handler($command);

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(20000, $accountAfter->balance->getAmount());
        self::assertSame(Currency::EUR, $accountAfter->balance->getCurrency());
    }

    public function testMultipleDepositsAccumulate(): void
    {
        $handler = $this->createDepositMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // First deposit
        $handler(new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 10000,
            currency: 'PLN',
        ));

        // Second deposit
        $handler(new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 5000,
            currency: 'PLN',
        ));

        // Third deposit
        $handler(new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 3000,
            currency: 'PLN',
        ));

        // Verify balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);

        self::assertNotNull($accountAfter);
        self::assertSame(18000, $accountAfter->balance->getAmount());

        // Verify transactions
        $transactions = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );

        self::assertCount(3, $transactions);
    }

    private function createDepositMoneyHandler(): DepositMoneyCommandHandler
    {
        return new DepositMoneyCommandHandler(
            $this->transactionRepository,
            $this->eventBus,
            $this->bankAccountRepository,
        );
    }

    private function openBankAccount(CustomerId $customerId, string $currency): void
    {
        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $command = new OpenBankAccountCommand($customerId->getValue(), $currency);
        $handler($command);
    }
}
