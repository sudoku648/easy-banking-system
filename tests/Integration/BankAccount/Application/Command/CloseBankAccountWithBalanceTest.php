<?php

declare(strict_types=1);

namespace App\Tests\Integration\BankAccount\Application\Command;

use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Command\CloseBankAccountCommandHandler;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Provider\ClockInterface;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Support\Provider\MockClock;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Application\Command\DepositMoneyCommandHandler;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionType;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class CloseBankAccountWithBalanceTest extends IntegrationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private UserRepositoryInterface $userRepository;
    private EventBus $eventBus;
    private MockClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->transactionRepository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
        $this->eventBus = self::getContainer()->get(EventBus::class);
        $this->clock = self::getContainer()->get(ClockInterface::class);
    }

    private function createCustomer(): CustomerId
    {
        $customer = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('customer_' . uniqid()),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Test'),
            lastName: LastName::fromString('Customer'),
        );

        $this->userRepository->save($customer);

        return CustomerId::fromString($customer->id->getValue());
    }

    public function testClosingAccountWithBalanceCreatesWithdrawalTransaction(): void
    {
        // Arrange: Create customer and open account
        $customerId = $this->createCustomer();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Deposit money into the account
        $depositHandler = new DepositMoneyCommandHandler(
            $this->transactionRepository,
            $this->eventBus,
            $this->bankAccountRepository,
            $this->clock,
        );
        $depositHandler(new DepositMoneyCommand(
            bankAccountId: $account->id->getValue(),
            amount: 50000, // 500.00 PLN
            currency: 'PLN',
        ));

        // Verify initial state
        $accountBefore = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountBefore);
        self::assertSame(50000, $accountBefore->balance->getAmount());

        // Get transactions before closing
        $transactionsBefore = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );
        self::assertCount(1, $transactionsBefore); // Only deposit transaction
        self::assertSame(TransactionType::CASH_DEPOSIT, $transactionsBefore[0]->type);

        // Act: Close the account
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler(new CloseBankAccountCommand($account->id->getValue()));

        // Assert: Account is closed with zero balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountAfter);
        self::assertFalse($accountAfter->isActive);
        self::assertTrue($accountAfter->balance->isZero());

        // Assert: Withdrawal transaction was created
        $transactionsAfter = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );
        self::assertCount(2, $transactionsAfter); // Deposit + withdrawal transactions

        // Find the withdrawal transaction
        $withdrawalTransaction = null;
        foreach ($transactionsAfter as $transaction) {
            if ($transaction->type === TransactionType::CASH_WITHDRAWAL) {
                $withdrawalTransaction = $transaction;
                break;
            }
        }

        self::assertNotNull($withdrawalTransaction, 'Withdrawal transaction should be created when closing account with balance');
        self::assertSame(50000, $withdrawalTransaction->amount->getAmount());
        self::assertSame('PLN', $withdrawalTransaction->amount->getCurrency()->value);
    }

    public function testClosingAccountWithZeroBalanceDoesNotCreateTransaction(): void
    {
        // Arrange: Create customer and open account with zero balance
        $customerId = $this->createCustomer();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Verify account has zero balance
        $accountBefore = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountBefore);
        self::assertTrue($accountBefore->balance->isZero());

        // Get transactions before closing
        $transactionsBefore = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );
        self::assertCount(0, $transactionsBefore); // No transactions

        // Act: Close the account
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler(new CloseBankAccountCommand($account->id->getValue()));

        // Assert: No withdrawal transaction should be created
        $transactionsAfter = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );
        self::assertCount(0, $transactionsAfter); // Still no transactions
    }
}
