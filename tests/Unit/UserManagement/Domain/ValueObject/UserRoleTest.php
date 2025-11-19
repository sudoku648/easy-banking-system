<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function testFromStringCreatesValidUserRole(): void
    {
        $role = UserRole::fromString('EMPLOYEE');

        self::assertSame(UserRole::EMPLOYEE, $role);
    }

    public function testFromStringThrowsExceptionForInvalidRole(): void
    {
        $this->expectException(\ValueError::class);

        UserRole::fromString('INVALID_ROLE');
    }

    public function testUserRoleHasCorrectValues(): void
    {
        self::assertSame('EMPLOYEE', UserRole::EMPLOYEE->value);
        self::assertSame('CUSTOMER', UserRole::CUSTOMER->value);
    }

    public function testIsEmployeeReturnsTrueForEmployeeRole(): void
    {
        self::assertTrue(UserRole::EMPLOYEE->isEmployee());
        self::assertFalse(UserRole::CUSTOMER->isEmployee());
    }

    public function testIsCustomerReturnsTrueForCustomerRole(): void
    {
        self::assertTrue(UserRole::CUSTOMER->isCustomer());
        self::assertFalse(UserRole::EMPLOYEE->isCustomer());
    }
}
