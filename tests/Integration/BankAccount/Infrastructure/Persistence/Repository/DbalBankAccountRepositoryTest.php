<?php

declare(strict_types=1);

namespace App\Tests\Integration\BankAccount\Infrastructure\Persistence\Repository;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Integration\IntegrationTestCase;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class DbalBankAccountRepositoryTest extends IntegrationTestCase
{
    private BankAccountRepositoryInterface $repository;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testSaveAndFindById(): void
    {
        $customerId = $this->createCustomer();
        $accountId = BankAccountId::generate();
        $iban = Iban::fromString('PL61109010140000071219812874');

        $account = BankAccount::open(
            id: $accountId,
            iban: $iban,
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->repository->save($account);

        $foundAccount = $this->repository->findById($accountId);

        self::assertNotNull($foundAccount);
        self::assertTrue($foundAccount->id->equals($accountId));
        self::assertTrue($foundAccount->customerId->equals($customerId));
        self::assertTrue($foundAccount->iban->equals($iban));
        self::assertSame(Currency::PLN, $foundAccount->balance->getCurrency());
        self::assertTrue($foundAccount->isActive);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $nonExistentId = BankAccountId::generate();

        $result = $this->repository->findById($nonExistentId);

        self::assertNull($result);
    }

    public function testFindByIban(): void
    {
        $customerId = $this->createCustomer();
        $iban = Iban::fromString('PL61109010140000071219812874');

        $account = BankAccount::open(
            id: BankAccountId::generate(),
            iban: $iban,
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->repository->save($account);

        $foundAccount = $this->repository->findByIban($iban);

        self::assertNotNull($foundAccount);
        self::assertTrue($foundAccount->iban->equals($iban));
    }

    public function testFindByIbanReturnsNullWhenNotFound(): void
    {
        $iban = Iban::fromString('PL61109010140000071219812874');

        $result = $this->repository->findByIban($iban);

        self::assertNull($result);
    }

    public function testFindByCustomerId(): void
    {
        $customerId = $this->createCustomer();

        $account1 = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL61109010140000071219812874'),
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $account2 = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL27114020040000300201355387'),
            customerId: $customerId,
            initialBalance: Money::zero(Currency::EUR),
        );

        $this->repository->save($account1);
        $this->repository->save($account2);

        $accounts = $this->repository->findByCustomerId($customerId);

        self::assertCount(2, $accounts);
        foreach ($accounts as $account) {
            self::assertTrue($account->customerId->equals($customerId));
        }
    }

    public function testFindByCustomerIdReturnsEmptyArrayWhenNotFound(): void
    {
        $customerId = CustomerId::generate();

        $result = $this->repository->findByCustomerId($customerId);

        self::assertSame([], $result);
    }

    public function testFindAllActive(): void
    {
        $customerId1 = $this->createCustomer();
        $customerId2 = $this->createCustomer();

        $activeAccount1 = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL61109010140000071219812874'),
            customerId: $customerId1,
            initialBalance: Money::zero(Currency::PLN),
        );

        $activeAccount2 = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL27114020040000300201355387'),
            customerId: $customerId2,
            initialBalance: Money::zero(Currency::EUR),
        );

        $closedAccount = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL83101010230000261395100000'),
            customerId: $customerId1,
            initialBalance: Money::zero(Currency::PLN),
        );
        $closedAccount->close();

        $this->repository->save($activeAccount1);
        $this->repository->save($activeAccount2);
        $this->repository->save($closedAccount);

        $activeAccounts = $this->repository->findAllActive();

        self::assertCount(2, $activeAccounts);
        foreach ($activeAccounts as $account) {
            self::assertTrue($account->isActive);
        }
    }

    public function testExistsByIban(): void
    {
        $customerId = $this->createCustomer();
        $iban = Iban::fromString('PL61109010140000071219812874');

        $account = BankAccount::open(
            id: BankAccountId::generate(),
            iban: $iban,
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->repository->save($account);

        self::assertTrue($this->repository->existsByIban($iban));
        self::assertFalse($this->repository->existsByIban(Iban::fromString('PL27114020040000300201355387')));
    }

    public function testUpdateAccount(): void
    {
        $customerId = $this->createCustomer();

        $account = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL61109010140000071219812874'),
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->repository->save($account);

        // Deposit money to change balance
        $account->deposit(new Money(10000, Currency::PLN));
        $this->repository->save($account);

        $updatedAccount = $this->repository->findById($account->id);

        self::assertNotNull($updatedAccount);
        self::assertSame(10000, $updatedAccount->balance->getAmount());
    }

    public function testCloseAccount(): void
    {
        $customerId = $this->createCustomer();

        $account = BankAccount::open(
            id: BankAccountId::generate(),
            iban: Iban::fromString('PL61109010140000071219812874'),
            customerId: $customerId,
            initialBalance: Money::zero(Currency::PLN),
        );

        $this->repository->save($account);

        $account->close();
        $this->repository->save($account);

        $closedAccount = $this->repository->findById($account->id);

        self::assertNotNull($closedAccount);
        self::assertFalse($closedAccount->isActive);
    }

    public function testNextIdentityGeneratesUniqueIds(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        self::assertInstanceOf(BankAccountId::class, $id1);
        self::assertInstanceOf(BankAccountId::class, $id2);
        self::assertFalse($id1->equals($id2));
    }

    public function testNextAccountNumberGeneratesUniqueNumbers(): void
    {
        $accountNumber1 = $this->repository->nextAccountNumber();
        $accountNumber2 = $this->repository->nextAccountNumber();

        self::assertIsString($accountNumber1);
        self::assertIsString($accountNumber2);
        self::assertNotSame($accountNumber1, $accountNumber2);
        self::assertSame(26, \strlen($accountNumber1)); // Polish account number length
        self::assertSame(26, \strlen($accountNumber2));
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
}
