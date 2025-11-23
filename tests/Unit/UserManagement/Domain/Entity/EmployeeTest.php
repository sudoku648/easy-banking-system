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
        $username = Username::fromString('john.doe');
        $password = HashedPassword::fromString('$2y$13$hashedpassword');
        $firstName = FirstName::fromString('John');
        $lastName = LastName::fromString('Doe');

        $employee = Employee::create($userId, $username, $password, $firstName, $lastName);

        self::assertSame($userId, $employee->id);
        self::assertSame($username, $employee->username);
        self::assertSame($password, $employee->password);
        self::assertSame($firstName, $employee->firstName);
        self::assertSame($lastName, $employee->lastName);
        self::assertTrue($employee->isActive);
    }

    public function testGetRoleReturnsEmployeeRole(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
        );

        self::assertSame(UserRole::EMPLOYEE, $employee->getRole());
    }

    public function testGetFullNameReturnsFormattedName(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
        );

        self::assertSame('John Doe', $employee->getFullName());
    }

    public function testDeactivateSetsActiveToFalse(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
        );

        $employee->deactivate();

        self::assertFalse($employee->isActive);
    }

    public function testActivateSetsActiveToTrue(): void
    {
        $employee = Employee::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
        );

        $employee->deactivate();
        $employee->activate();

        self::assertTrue($employee->isActive);
    }
}
