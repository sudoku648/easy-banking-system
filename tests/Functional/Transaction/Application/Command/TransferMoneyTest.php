<?php

declare(strict_types=1);

namespace App\Tests\Functional\Transaction\Application\Command;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Shared\ApplicationTestCase;
use App\Tests\Support\Event\InMemoryEventBus;
use App\Tests\Support\Provider\MockExchangeRateProvider;
use App\Transaction\Application\Command\TransferMoneyCommand;
use App\Transaction\Application\Command\TransferMoneyCommandHandler;
use App\Transaction\Domain\Event\MoneyTransferred;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\TransactionType;

final class TransferMoneyTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private ExchangeRateProviderInterface $exchangeRateProvider;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->transactionRepository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->exchangeRateProvider = self::getContainer()->get(ExchangeRateProviderInterface::class);
        $this->eventBus = self::getContainer()->get(EventBus::class);

        // Setup default exchange rates for MockExchangeRateProvider
        if ($this->exchangeRateProvider instanceof MockExchangeRateProvider) {
            $this->exchangeRateProvider->setRate(Currency::PLN, Currency::EUR, 0.23);
            $this->exchangeRateProvider->setRate(Currency::EUR, Currency::PLN, 4.35);
        }
    }

    private function getEventBus(): ?InMemoryEventBus
    {
        if ($this->eventBus instanceof InMemoryEventBus) {
            return $this->eventBus;
        }
        return null;
    }

    private function isUsingInMemoryEventBus(): bool
    {
        return $this->eventBus instanceof InMemoryEventBus;
    }

    public function testTransferMoneyBetweenAccountsWithSameCurrency(): void
    {
        $handler = $this->createTransferMoneyHandler();

        // Create two accounts
        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->openBankAccount($customerId1, 'PLN');
        $this->openBankAccount($customerId2, 'PLN');

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        $fromAccount = $accounts1[0];
        $toAccount = $accounts2[0];

        // Add balance to source account
        $fromAccount->deposit(new Money(100000, Currency::PLN)); // 1000.00 PLN
        $this->bankAccountRepository->save($fromAccount);

        // Transfer money
        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 50000, // 500.00 PLN
            currency: 'PLN',
        );

        $handler($command);

        // Verify balances
        $fromAccountAfter = $this->bankAccountRepository->findById($fromAccount->getId());
        $toAccountAfter = $this->bankAccountRepository->findById($toAccount->getId());

        self::assertNotNull($fromAccountAfter);
        self::assertNotNull($toAccountAfter);
        self::assertSame(50000, $fromAccountAfter->getBalance()->getAmount());
        self::assertSame(50000, $toAccountAfter->getBalance()->getAmount());
    }

    public function testTransferMoneyBetweenAccountsWithDifferentCurrencies(): void
    {
        $handler = $this->createTransferMoneyHandler();

        // Create two accounts with different currencies
        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->openBankAccount($customerId1, 'PLN');
        $this->openBankAccount($customerId2, 'EUR');

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        $fromAccount = $accounts1[0];
        $toAccount = $accounts2[0];

        // Add balance to source account
        $fromAccount->deposit(new Money(100000, Currency::PLN)); // 1000.00 PLN
        $this->bankAccountRepository->save($fromAccount);

        // Transfer 100.00 PLN (should be converted to EUR)
        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 10000, // 100.00 PLN
            currency: 'PLN',
        );

        $handler($command);

        // Verify balances
        $fromAccountAfter = $this->bankAccountRepository->findById($fromAccount->getId());
        $toAccountAfter = $this->bankAccountRepository->findById($toAccount->getId());

        self::assertNotNull($fromAccountAfter);
        self::assertNotNull($toAccountAfter);
        self::assertSame(90000, $fromAccountAfter->getBalance()->getAmount()); // 900.00 PLN
        self::assertSame(2300, $toAccountAfter->getBalance()->getAmount()); // 23.00 EUR (100 PLN * 0.23)
    }

    public function testTransferMoneyCreatesTransactions(): void
    {
        $handler = $this->createTransferMoneyHandler();

        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->openBankAccount($customerId1, 'PLN');
        $this->openBankAccount($customerId2, 'PLN');

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        $fromAccount = $accounts1[0];
        $toAccount = $accounts2[0];

        $fromAccount->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($fromAccount);

        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 50000,
            currency: 'PLN',
        );

        $handler($command);

        // Verify transactions
        $fromTransactions = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($fromAccount->getId()->getValue()),
        );

        $toTransactions = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($toAccount->getId()->getValue()),
        );

        self::assertCount(1, $fromTransactions);
        self::assertCount(1, $toTransactions);

        self::assertSame(TransactionType::TRANSFER_WITHDRAWAL, $fromTransactions[0]->getType());
        self::assertSame(TransactionType::TRANSFER_DEPOSIT, $toTransactions[0]->getType());

        self::assertSame(50000, $fromTransactions[0]->getAmount()->getAmount());
        self::assertSame(50000, $toTransactions[0]->getAmount()->getAmount());
    }

    public function testTransferMoneyDispatchesMoneyTransferredEvent(): void
    {
        if (!$this->isUsingInMemoryEventBus()) {
            self::markTestSkipped('Event assertions only work with InMemoryEventBus (functional mode)');
        }

        $handler = $this->createTransferMoneyHandler();

        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->openBankAccount($customerId1, 'PLN');
        $this->openBankAccount($customerId2, 'PLN');

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        $fromAccount = $accounts1[0];
        $toAccount = $accounts2[0];

        $fromAccount->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($fromAccount);

        $eventBus = $this->getEventBus();
        $eventBus->clear();

        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 50000,
            currency: 'PLN',
        );

        $handler($command);

        $events = $eventBus->getDispatchedEventsOfType(MoneyTransferred::class);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertSame(50000, $event->amount->getAmount());
        self::assertTrue($event->fromIban->equals($fromAccount->getIban()));
        self::assertTrue($event->toIban->equals($toAccount->getIban()));
    }

    public function testTransferMoneyThrowsExceptionForInsufficientFunds(): void
    {
        $handler = $this->createTransferMoneyHandler();

        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->openBankAccount($customerId1, 'PLN');
        $this->openBankAccount($customerId2, 'PLN');

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        $fromAccount = $accounts1[0];
        $toAccount = $accounts2[0];

        $fromAccount->deposit(new Money(10000, Currency::PLN)); // Only 100.00 PLN
        $this->bankAccountRepository->save($fromAccount);

        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 50000, // Trying to transfer 500.00 PLN
            currency: 'PLN',
        );

        $this->expectException(InsufficientFundsException::class);

        $handler($command);
    }

    public function testTransferMoneyThrowsExceptionForNonExistentSourceAccount(): void
    {
        $handler = $this->createTransferMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $toAccount = $accounts[0];

        $nonExistentId = CustomerId::generate()->getValue();

        $command = new TransferMoneyCommand(
            fromBankAccountId: $nonExistentId,
            toBankAccountId: $toAccount->getId()->getValue(),
            amount: 10000,
            currency: 'PLN',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Source bank account not found');

        $handler($command);
    }

    public function testTransferMoneyThrowsExceptionForNonExistentTargetAccount(): void
    {
        $handler = $this->createTransferMoneyHandler();

        $customerId = CustomerId::generate();
        $this->openBankAccount($customerId, 'PLN');

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $fromAccount = $accounts[0];

        $fromAccount->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($fromAccount);

        $nonExistentId = CustomerId::generate()->getValue();

        $command = new TransferMoneyCommand(
            fromBankAccountId: $fromAccount->getId()->getValue(),
            toBankAccountId: $nonExistentId,
            amount: 10000,
            currency: 'PLN',
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Target bank account not found');

        $handler($command);
    }

    private function createTransferMoneyHandler(): TransferMoneyCommandHandler
    {
        return new TransferMoneyCommandHandler(
            $this->transactionRepository,
            $this->exchangeRateProvider,
            $this->eventBus,
            $this->bankAccountRepository,
        );
    }

    private function openBankAccount(CustomerId $customerId, string $currency): void
    {
        $this->ensureCustomerExists($customerId->getValue());
        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $command = new OpenBankAccountCommand($customerId->getValue(), $currency);
        $handler($command);
    }
}
