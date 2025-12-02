<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\City;
use App\UserManagement\Domain\ValueObject\Country;
use App\UserManagement\Domain\ValueObject\PostalCode;
use App\UserManagement\Domain\ValueObject\Street;

trait AddressTestHelper
{
    protected function createTestAddress(
        string $street = 'Main Street 123',
        string $city = 'Warsaw',
        string $postalCode = '00-001',
        string $country = 'Poland',
    ): Address {
        return new Address(
            Street::fromString($street),
            City::fromString($city),
            PostalCode::fromString($postalCode),
            Country::fromString($country),
        );
    }

    protected function createTestCorrespondenceAddress(): Address
    {
        return $this->createTestAddress(
            'Correspondence Street 456',
            'Krakow',
            '30-001',
            'Poland',
        );
    }
}
