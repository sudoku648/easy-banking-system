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
        $username = new Username('jane.smith');
        $password = new HashedPassword('$2y$13$hashedpassword');
        $firstName = new FirstName('Jane');
        $lastName = new LastName('Smith');

        $customer = Customer::create($userId, $username, $password, $firstName, $lastName);

        self::assertSame($userId, $customer->getId());
        self::assertSame($username, $customer->getUsername());
        self::assertSame($password, $customer->getPassword());
        self::assertSame($firstName, $customer->getFirstName());
        self::assertSame($lastName, $customer->getLastName());
        self::assertTrue($customer->isActive());
    }

    public function testGetRoleReturnsCustomerRole(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            new Username('jane.smith'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('Jane'),
            new LastName('Smith'),
        );

        self::assertSame(UserRole::CUSTOMER, $customer->getRole());
    }

    public function testGetFullNameReturnsFormattedName(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            new Username('jane.smith'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('Jane'),
            new LastName('Smith'),
        );

        self::assertSame('Jane Smith', $customer->getFullName());
    }

    public function testDeactivateSetsActiveToFalse(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            new Username('jane.smith'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('Jane'),
            new LastName('Smith'),
        );

        $customer->deactivate();

        self::assertFalse($customer->isActive());
    }

    public function testActivateSetsActiveToTrue(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            new Username('jane.smith'),
            new HashedPassword('$2y$13$hashedpassword'),
            new FirstName('Jane'),
            new LastName('Smith'),
        );

        $customer->deactivate();
        $customer->activate();

        self::assertTrue($customer->isActive());
    }
}
