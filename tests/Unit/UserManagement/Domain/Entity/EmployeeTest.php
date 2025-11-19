<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\Entity;

use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function testCreateCreatesValidEmployee(): void
    {
        $userId = UserId::generate();
        $username = new Username('john.doe');
        $password = new HashedPassword('$2y$13$hashedpassword');
        $firstName = new FirstName('John');
        $lastName = new LastName('Doe');

        $employee = Employee::create($userId, $username, $password, $firstName, $lastName);

        self::assertSame($userId, $employee->getId());
        self::assertSame($username, $employee->getUsername());
        self::assertSame($password, $employee->getPassword());
        self::assertSame($firstName, $employee->getFirstName());
        self::assertSame($lastName, $employee->getLastName());
        self::assertTrue($employee->isActive());
    }

    public function testGetRoleReturnsEmployeeRole(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            new Username('john.doe'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('John'),
            new LastName('Doe'),
        );

        self::assertSame(UserRole::EMPLOYEE, $employee->getRole());
    }

    public function testGetFullNameReturnsFormattedName(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            new Username('john.doe'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('John'),
            new LastName('Doe'),
        );

        self::assertSame('John Doe', $employee->getFullName());
    }

    public function testDeactivateSetsActiveToFalse(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            new Username('john.doe'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('John'),
            new LastName('Doe'),
        );

        $employee->deactivate();

        self::assertFalse($employee->isActive());
    }

    public function testActivateSetsActiveToTrue(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            new Username('john.doe'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('John'),
            new LastName('Doe'),
        );

        $employee->deactivate();
        $employee->activate();

        self::assertTrue($employee->isActive());
    }
}
