<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\Entity;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    public function testCreateCreatesValidCustomer(): void
    {
        $userId = UserId::generate();
        $username = Username::fromString('jane.smith');
        $password = HashedPassword::fromString('$2y$13$hashedpassword');
        $firstName = FirstName::fromString('Jane');
        $lastName = LastName::fromString('Smith');

        $customer = Customer::create($userId, $username, $password, $firstName, $lastName);

        self::assertSame($userId, $customer->id);
        self::assertSame($username, $customer->username);
        self::assertSame($password, $customer->password);
        self::assertSame($firstName, $customer->firstName);
        self::assertSame($lastName, $customer->lastName);
        self::assertTrue($customer->isActive);
    }

    public function testGetRoleReturnsCustomerRole(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
        );

        self::assertSame(UserRole::CUSTOMER, $customer->getRole());
    }

    public function testGetFullNameReturnsFormattedName(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
        );

        self::assertSame('Jane Smith', $customer->getFullName());
    }

    public function testDeactivateSetsActiveToFalse(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
        );

        $customer->deactivate();

        self::assertFalse($customer->isActive);
    }

    public function testActivateSetsActiveToTrue(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
        );

        $customer->deactivate();
        $customer->activate();

        self::assertTrue($customer->isActive);
    }
}
