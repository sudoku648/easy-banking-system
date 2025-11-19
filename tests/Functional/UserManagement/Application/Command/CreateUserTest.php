<?php

declare(strict_types=1);

namespace App\Tests\Functional\UserManagement\Application\Command;

use App\Tests\Support\Repository\InMemoryUserRepository;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Application\Command\CreateCustomerCommandHandler;
use App\UserManagement\Application\Command\CreateEmployeeCommand;
use App\UserManagement\Application\Command\CreateEmployeeCommandHandler;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\Exception\UsernameAlreadyExistsException;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

final class CreateUserTest extends TestCase
{
    private InMemoryUserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
    }

    protected function tearDown(): void
    {
        $this->userRepository->clear();
    }

    public function testCreateCustomerCreatesNewCustomer(): void
    {
        $handler = new CreateCustomerCommandHandler($this->userRepository);
        $command = new CreateCustomerCommand(
            username: 'john.doe',
            password: 'SecurePassword123!',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler($command);

        $user = $this->userRepository->findByUsername(new Username('john.doe'));

        self::assertInstanceOf(Customer::class, $user);
        self::assertSame('john.doe', $user->getUsername()->getValue());
        self::assertSame('John', $user->getFirstName()->getValue());
        self::assertSame('Doe', $user->getLastName()->getValue());
        self::assertSame(UserRole::CUSTOMER, $user->getRole());
        self::assertTrue($user->isActive());
        self::assertTrue($user->getPassword()->verify('SecurePassword123!'));
    }

    public function testCreateCustomerThrowsExceptionForDuplicateUsername(): void
    {
        $handler = new CreateCustomerCommandHandler($this->userRepository);
        $command = new CreateCustomerCommand(
            username: 'john.doe',
            password: 'SecurePassword123!',
            firstName: 'John',
            lastName: 'Doe',
        );

        $handler($command);

        $this->expectException(UsernameAlreadyExistsException::class);
        $this->expectExceptionMessage('Username "john.doe" already exists');

        $handler($command);
    }

    public function testCreateEmployeeCreatesNewEmployee(): void
    {
        $handler = new CreateEmployeeCommandHandler($this->userRepository);
        $command = new CreateEmployeeCommand(
            username: 'jane.smith',
            password: 'SecurePassword123!',
            firstName: 'Jane',
            lastName: 'Smith',
        );

        $handler($command);

        $user = $this->userRepository->findByUsername(new Username('jane.smith'));

        self::assertInstanceOf(Employee::class, $user);
        self::assertSame('jane.smith', $user->getUsername()->getValue());
        self::assertSame('Jane', $user->getFirstName()->getValue());
        self::assertSame('Smith', $user->getLastName()->getValue());
        self::assertSame(UserRole::EMPLOYEE, $user->getRole());
        self::assertTrue($user->isActive());
        self::assertTrue($user->getPassword()->verify('SecurePassword123!'));
    }

    public function testCreateEmployeeThrowsExceptionForDuplicateUsername(): void
    {
        $handler = new CreateEmployeeCommandHandler($this->userRepository);
        $command = new CreateEmployeeCommand(
            username: 'jane.smith',
            password: 'SecurePassword123!',
            firstName: 'Jane',
            lastName: 'Smith',
        );

        $handler($command);

        $this->expectException(UsernameAlreadyExistsException::class);
        $this->expectExceptionMessage('Username "jane.smith" already exists');

        $handler($command);
    }

    public function testCreateCustomerAndEmployeeCanHaveDifferentUsernames(): void
    {
        $customerHandler = new CreateCustomerCommandHandler($this->userRepository);
        $employeeHandler = new CreateEmployeeCommandHandler($this->userRepository);

        $customerCommand = new CreateCustomerCommand(
            username: 'user1',
            password: 'Password123!',
            firstName: 'First',
            lastName: 'User',
        );

        $employeeCommand = new CreateEmployeeCommand(
            username: 'user2',
            password: 'Password123!',
            firstName: 'Second',
            lastName: 'User',
        );

        $customerHandler($customerCommand);
        $employeeHandler($employeeCommand);

        $customer = $this->userRepository->findByUsername(new Username('user1'));
        $employee = $this->userRepository->findByUsername(new Username('user2'));

        self::assertInstanceOf(Customer::class, $customer);
        self::assertInstanceOf(Employee::class, $employee);
        self::assertNotSame($customer->getId()->getValue(), $employee->getId()->getValue());
    }
}
