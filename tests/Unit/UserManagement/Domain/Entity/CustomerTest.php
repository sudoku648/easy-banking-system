<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\Entity;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\AddressType;
use App\UserManagement\Domain\ValueObject\City;
use App\UserManagement\Domain\ValueObject\Country;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\PostalCode;
use App\UserManagement\Domain\ValueObject\Street;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    private function createTestAddress(): Address
    {
        return new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );
    }

    private function createTestCorrespondenceAddress(): Address
    {
        return new Address(
            Street::fromString('Correspondence Street 456'),
            City::fromString('Krakow'),
            PostalCode::fromString('30-001'),
            Country::fromString('Poland'),
        );
    }

    public function testCreateCreatesValidCustomer(): void
    {
        $userId = UserId::generate();
        $username = Username::fromString('jane.smith');
        $password = HashedPassword::fromString('$2y$13$hashedpassword');
        $firstName = FirstName::fromString('Jane');
        $lastName = LastName::fromString('Smith');
        $address = $this->createTestAddress();

        $customer = Customer::create($userId, $username, $password, $firstName, $lastName, $address, $this->createTestCorrespondenceAddress());

        self::assertSame($userId, $customer->id);
        self::assertSame($username, $customer->username);
        self::assertSame($password, $customer->password);
        self::assertSame($firstName, $customer->firstName);
        self::assertSame($lastName, $customer->lastName);
        self::assertTrue($customer->isActive);
        self::assertNotNull($customer->getPermanentResidenceAddress());
        self::assertSame(AddressType::PERMANENT_RESIDENCE, $customer->getPermanentResidenceAddress()->type);
    }

    public function testGetRoleReturnsCustomerRole(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
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
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
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
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
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
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        $customer->deactivate();
        $customer->activate();

        self::assertTrue($customer->isActive);
    }

    public function testAddCorrespondenceAddress(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        $correspondenceAddress = new Address(
            Street::fromString('Office Street 456'),
            City::fromString('Krakow'),
            PostalCode::fromString('30-001'),
            Country::fromString('Poland'),
        );

        $customer->addCorrespondenceAddress($correspondenceAddress);

        $correspondenceAddresses = array_values($customer->getCorrespondenceAddresses());
        self::assertCount(2, $correspondenceAddresses);
        self::assertSame(AddressType::CORRESPONDENCE, $correspondenceAddresses[0]->type);
    }

    public function testGetAllAddressesReturnsAllAddresses(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('jane.smith'),
            HashedPassword::fromString('$2y$13$hashedpassword'),
            FirstName::fromString('Jane'),
            LastName::fromString('Smith'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        $correspondenceAddress = new Address(
            Street::fromString('Office Street 456'),
            City::fromString('Krakow'),
            PostalCode::fromString('30-001'),
            Country::fromString('Poland'),
        );

        $customer->addCorrespondenceAddress($correspondenceAddress);

        $allAddresses = $customer->getAllAddresses();
        self::assertCount(3, $allAddresses); // 1 permanent + 2 correspondence
    }
}
