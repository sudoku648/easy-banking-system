<?php

declare(strict_types=1);

namespace App\Tests\Functional\BankAccount\Application\Command;

use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Command\CloseBankAccountCommandHandler;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Shared\ApplicationTestCase;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Application\Command\DepositMoneyCommandHandler;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransactionType;

final class CloseBankAccountWithBalanceTest extends ApplicationTestCase
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

    public function testClosingAccountWithBalanceCreatesWithdrawalTransaction(): void
    {
        // Arrange: Open account and deposit money
        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Deposit money into the account
        $depositHandler = new DepositMoneyCommandHandler(
            $this->transactionRepository,
            $this->eventBus,
            $this->bankAccountRepository,
        );
        $depositHandler(new DepositMoneyCommand(
            bankAccountId: $account->getId()->getValue(),
            amount: 50000, // 500.00 PLN
            currency: 'PLN',
        ));

        // Verify initial state
        $accountBefore = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountBefore);
        self::assertSame(50000, $accountBefore->getBalance()->getAmount());

        // Get transactions before closing
        $transactionsBefore = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
        );
        self::assertCount(1, $transactionsBefore); // Only deposit transaction
        self::assertSame(TransactionType::CASH_DEPOSIT, $transactionsBefore[0]->getType());

        // Act: Close the account
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler(new CloseBankAccountCommand($account->getId()->getValue()));

        // Assert: Account is closed with zero balance
        $accountAfter = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountAfter);
        self::assertFalse($accountAfter->isActive());
        self::assertTrue($accountAfter->getBalance()->isZero());

        // Assert: Withdrawal transaction was created
        $transactionsAfter = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
        );
        self::assertCount(2, $transactionsAfter); // Deposit + withdrawal transactions

        // Find the withdrawal transaction
        $withdrawalTransaction = null;
        foreach ($transactionsAfter as $transaction) {
            if ($transaction->getType() === TransactionType::CASH_WITHDRAWAL) {
                $withdrawalTransaction = $transaction;
                break;
            }
        }

        self::assertNotNull($withdrawalTransaction, 'Withdrawal transaction should be created when closing account with balance');
        self::assertSame(50000, $withdrawalTransaction->getAmount()->getAmount());
        self::assertSame('PLN', $withdrawalTransaction->getAmount()->getCurrency()->value);
    }

    public function testClosingAccountWithZeroBalanceDoesNotCreateTransaction(): void
    {
        // Arrange: Open account with zero balance
        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Verify account has zero balance
        $accountBefore = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountBefore);
        self::assertTrue($accountBefore->getBalance()->isZero());

        // Get transactions before closing
        $transactionsBefore = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
        );
        self::assertCount(0, $transactionsBefore); // No transactions

        // Act: Close the account
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler(new CloseBankAccountCommand($account->getId()->getValue()));

        // Assert: No withdrawal transaction should be created
        $transactionsAfter = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
        );
        self::assertCount(0, $transactionsAfter); // Still no transactions
    }
}
