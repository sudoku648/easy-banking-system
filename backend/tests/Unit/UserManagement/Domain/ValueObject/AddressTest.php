<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\City;
use App\UserManagement\Domain\ValueObject\Country;
use App\UserManagement\Domain\ValueObject\PostalCode;
use App\UserManagement\Domain\ValueObject\Street;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testCreateValidAddress(): void
    {
        $address = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        self::assertSame('Main Street 123', $address->street->getValue());
        self::assertSame('Warsaw', $address->city->getValue());
        self::assertSame('00-001', $address->postalCode->getValue());
        self::assertSame('Poland', $address->country->getValue());
    }

    public function testGetValueReturnsFormattedAddress(): void
    {
        $address = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        self::assertSame('Main Street 123, 00-001 Warsaw, Poland', $address->getValue());
    }

    public function testFromRawCreatesAddressFromArray(): void
    {
        $data = [
            'street' => 'Office Street 456',
            'city' => 'Krakow',
            'postal_code' => '30-001',
            'country' => 'Poland',
        ];

        $address = Address::fromRaw($data);

        self::assertSame('Office Street 456', $address->street->getValue());
        self::assertSame('Krakow', $address->city->getValue());
        self::assertSame('30-001', $address->postalCode->getValue());
        self::assertSame('Poland', $address->country->getValue());
    }

    public function testEqualsReturnsTrueForSameAddress(): void
    {
        $address1 = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        $address2 = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        self::assertTrue($address1->equals($address2));
    }

    public function testEqualsReturnsFalseForDifferentAddress(): void
    {
        $address1 = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        $address2 = new Address(
            Street::fromString('Office Street 456'),
            City::fromString('Krakow'),
            PostalCode::fromString('30-001'),
            Country::fromString('Poland'),
        );

        self::assertFalse($address1->equals($address2));
    }

    public function testToStringReturnsFormattedAddress(): void
    {
        $address = new Address(
            Street::fromString('Main Street 123'),
            City::fromString('Warsaw'),
            PostalCode::fromString('00-001'),
            Country::fromString('Poland'),
        );

        self::assertSame('Main Street 123, 00-001 Warsaw, Poland', (string) $address);
    }
}
