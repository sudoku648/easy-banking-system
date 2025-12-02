<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserManagement\Infrastructure\Persistence\Repository;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Support\AddressTestHelper;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\Locale;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class DbalUserRepositoryTest extends IntegrationTestCase
{
    use AddressTestHelper;

    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testSaveAndFindCustomer(): void
    {
        $customer = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('john.doe'),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('John'),
            lastName: LastName::fromString('Doe'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $this->repository->save($customer);

        $foundCustomer = $this->repository->findById($customer->id);

        self::assertNotNull($foundCustomer);
        self::assertInstanceOf(Customer::class, $foundCustomer);
        self::assertTrue($foundCustomer->id->equals($customer->id));
        self::assertTrue($foundCustomer->username->equals($customer->username));
        self::assertTrue($foundCustomer->firstName->equals($customer->firstName));
        self::assertTrue($foundCustomer->lastName->equals($customer->lastName));
    }

    public function testSaveAndFindEmployee(): void
    {
        $employee = Employee::create(
            id: UserId::generate(),
            username: Username::fromString('jane.smith'),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Jane'),
            lastName: LastName::fromString('Smith'),
        );

        $this->repository->save($employee);

        $foundEmployee = $this->repository->findById($employee->id);

        self::assertNotNull($foundEmployee);
        self::assertInstanceOf(Employee::class, $foundEmployee);
        self::assertTrue($foundEmployee->id->equals($employee->id));
        self::assertTrue($foundEmployee->username->equals($employee->username));
    }

    public function testFindByIdReturnsNullWhenUserNotFound(): void
    {
        $nonExistentId = UserId::generate();

        $result = $this->repository->findById($nonExistentId);

        self::assertNull($result);
    }

    public function testFindByUsername(): void
    {
        $username = Username::fromString('test.user');
        $customer = Customer::create(
            id: UserId::generate(),
            username: $username,
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Test'),
            lastName: LastName::fromString('User'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $this->repository->save($customer);

        $foundUser = $this->repository->findByUsername($username);

        self::assertNotNull($foundUser);
        self::assertTrue($foundUser->id->equals($customer->id));
        self::assertTrue($foundUser->username->equals($username));
    }

    public function testFindByUsernameReturnsNullWhenNotFound(): void
    {
        $username = Username::fromString('nonexistent.user');

        $result = $this->repository->findByUsername($username);

        self::assertNull($result);
    }

    public function testExistsByUsername(): void
    {
        $username = Username::fromString('existing.user');
        $customer = Customer::create(
            id: UserId::generate(),
            username: $username,
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Existing'),
            lastName: LastName::fromString('User'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $this->repository->save($customer);

        self::assertTrue($this->repository->existsByUsername($username));
        self::assertFalse($this->repository->existsByUsername(Username::fromString('nonexistent.user')));
    }

    public function testFindAllCustomers(): void
    {
        $customer1 = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('customer1'),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Customer'),
            lastName: LastName::fromString('One'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $customer2 = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('customer2'),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Customer'),
            lastName: LastName::fromString('Two'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $employee = Employee::create(
            id: UserId::generate(),
            username: Username::fromString('employee1'),
            password: HashedPassword::fromString('$2y$10$hashedPassword'),
            firstName: FirstName::fromString('Employee'),
            lastName: LastName::fromString('One'),
        );

        $this->repository->save($customer1);
        $this->repository->save($customer2);
        $this->repository->save($employee);

        $customers = $this->repository->findAllCustomers();

        self::assertCount(2, $customers);
        foreach ($customers as $customer) {
            self::assertInstanceOf(Customer::class, $customer);
        }
    }

    public function testUpdateUser(): void
    {
        $customer = Customer::create(
            id: UserId::generate(),
            username: Username::fromString('original.username'),
            password: HashedPassword::fromString('$2y$10$originalPassword'),
            firstName: FirstName::fromString('Original'),
            lastName: LastName::fromString('Name'),
            permanentResidenceAddress: $this->createTestAddress(),
            correspondenceAddress: $this->createTestCorrespondenceAddress(),
        );

        $this->repository->save($customer);

        // Verify customer is active initially
        self::assertTrue($customer->isActive);

        // Change locale
        $customer->changeLocale(Locale::ENGLISH);
        $this->repository->save($customer);

        $updatedCustomer = $this->repository->findById($customer->id);

        self::assertNotNull($updatedCustomer);
        self::assertSame(Locale::ENGLISH, $updatedCustomer->locale);
    }

    public function testNextIdentityGeneratesUniqueIds(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        self::assertInstanceOf(UserId::class, $id1);
        self::assertInstanceOf(UserId::class, $id2);
        self::assertFalse($id1->equals($id2));
    }
}
