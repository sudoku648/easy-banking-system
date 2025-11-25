<?php

declare(strict_types=1);

namespace App\Tests\Integration\Transaction\Infrastructure\Persistence\Repository;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Integration\IntegrationTestCase;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use App\Transaction\Domain\ValueObject\TransactionId;
use App\Transaction\Domain\ValueObject\TransactionType;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class DbalTransactionRepositoryTest extends IntegrationTestCase
{
    private TransactionRepositoryInterface $repository;
    private BankAccountRepositoryInterface $bankAccountRepository;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testSaveAndFindById(): void
    {
        $bankAccountId = $this->createBankAccount();
        $transactionId = TransactionId::generate();
        $occurredAt = new \DateTimeImmutable();

        $transaction = Transaction::createCashDeposit(
            id: $transactionId,
            bankAccountId: $bankAccountId,
            amount: new Money(10000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $this->repository->save($transaction);

        $foundTransaction = $this->repository->findById($transactionId);

        self::assertNotNull($foundTransaction);
        self::assertTrue($foundTransaction->id->equals($transactionId));
        self::assertTrue($foundTransaction->bankAccountId->equals($bankAccountId));
        self::assertSame(TransactionType::CASH_DEPOSIT, $foundTransaction->type);
        self::assertSame(10000, $foundTransaction->amount->getAmount());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $nonExistentId = TransactionId::generate();

        $result = $this->repository->findById($nonExistentId);

        self::assertNull($result);
    }

    public function testFindByBankAccountId(): void
    {
        $bankAccountId = $this->createBankAccount();
        $occurredAt = new \DateTimeImmutable();

        $transaction1 = Transaction::createCashDeposit(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId,
            amount: new Money(10000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $transaction2 = Transaction::createCashWithdrawal(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId,
            amount: new Money(5000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $this->repository->save($transaction1);
        $this->repository->save($transaction2);

        $transactions = $this->repository->findByBankAccountId($bankAccountId);

        self::assertCount(2, $transactions);
        foreach ($transactions as $transaction) {
            self::assertTrue($transaction->bankAccountId->equals($bankAccountId));
        }
    }

    public function testFindByBankAccountIdReturnsEmptyArrayWhenNotFound(): void
    {
        $bankAccountId = BankAccountId::generate();

        $result = $this->repository->findByBankAccountId($bankAccountId);

        self::assertSame([], $result);
    }

    public function testFindByBankAccountIds(): void
    {
        $bankAccountId1 = $this->createBankAccount();
        $bankAccountId2 = $this->createBankAccount();
        $bankAccountId3 = $this->createBankAccount();
        $occurredAt = new \DateTimeImmutable();

        $transaction1 = Transaction::createCashDeposit(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId1,
            amount: new Money(10000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $transaction2 = Transaction::createCashDeposit(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId2,
            amount: new Money(20000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $transaction3 = Transaction::createCashDeposit(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId3,
            amount: new Money(30000, Currency::PLN),
            occurredAt: $occurredAt,
        );

        $this->repository->save($transaction1);
        $this->repository->save($transaction2);
        $this->repository->save($transaction3);

        // Find transactions for accounts 1 and 2
        $transactions = $this->repository->findByBankAccountIds([$bankAccountId1, $bankAccountId2]);

        self::assertCount(2, $transactions);

        $accountIds = array_map(
            fn (Transaction $t): string => $t->bankAccountId->getValue(),
            $transactions,
        );

        self::assertContains($bankAccountId1->getValue(), $accountIds);
        self::assertContains($bankAccountId2->getValue(), $accountIds);
        self::assertNotContains($bankAccountId3->getValue(), $accountIds);
    }

    public function testFindByBankAccountIdsReturnsEmptyArrayWhenNotFound(): void
    {
        $bankAccountId1 = BankAccountId::generate();
        $bankAccountId2 = BankAccountId::generate();

        $result = $this->repository->findByBankAccountIds([$bankAccountId1, $bankAccountId2]);

        self::assertSame([], $result);
    }

    public function testSaveTransactionWithExchangeRate(): void
    {
        $bankAccountId = $this->createBankAccount();
        $occurredAt = new \DateTimeImmutable();
        $exchangeRate = new ExchangeRate(Currency::EUR, Currency::PLN, 5.0);

        $transaction = Transaction::createTransferDeposit(
            id: TransactionId::generate(),
            bankAccountId: $bankAccountId,
            amount: new Money(10000, Currency::PLN),
            originalAmount: new Money(2000, Currency::EUR),
            exchangeRate: $exchangeRate,
            occurredAt: $occurredAt,
        );

        $this->repository->save($transaction);

        $foundTransaction = $this->repository->findById($transaction->id);

        self::assertNotNull($foundTransaction);
        self::assertSame(5.0, $foundTransaction->exchangeRate->getRate());
        self::assertSame(2000, $foundTransaction->originalAmount->getAmount());
        self::assertSame(Currency::EUR, $foundTransaction->originalAmount->getCurrency());
        self::assertSame(10000, $foundTransaction->amount->getAmount());
        self::assertSame(Currency::PLN, $foundTransaction->amount->getCurrency());
    }

    public function testNextIdentityGeneratesUniqueIds(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        self::assertInstanceOf(TransactionId::class, $id1);
        self::assertInstanceOf(TransactionId::class, $id2);
        self::assertFalse($id1->equals($id2));
    }

    public function testMultipleTransactionsForSameBankAccount(): void
    {
        $bankAccountId = $this->createBankAccount();
        $occurredAt = new \DateTimeImmutable();

        $transactions = [];
        for ($i = 1; $i <= 5; $i++) {
            $transaction = Transaction::createCashDeposit(
                id: TransactionId::generate(),
                bankAccountId: $bankAccountId,
                amount: new Money($i * 1000, Currency::PLN),
                occurredAt: $occurredAt,
            );
            $this->repository->save($transaction);
            $transactions[] = $transaction;
        }

        $foundTransactions = $this->repository->findByBankAccountId($bankAccountId);

        self::assertCount(5, $foundTransactions);
    }

    private function createBankAccount(): BankAccountId
    {
        $customer = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('customer_' . uniqid()),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Test'),
            lastName: LastName::fromString('Customer'),
        );

        $this->userRepository->save($customer);

        $customerId = CustomerId::fromString($customer->id->getValue());
        $bankAccountId = \App\BankAccount\Domain\ValueObject\BankAccountId::generate();

        $account = BankAccount::open(
            id: $bankAccountId,
            iban: Iban::fromString('PL' . str_pad((string) mt_rand(1000, 9999) . mt_rand(10000000000000000, 99999999999999999), 26, '0', STR_PAD_LEFT)),
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->bankAccountRepository->save($account);

        return BankAccountId::fromString($bankAccountId->getValue());
    }
}
