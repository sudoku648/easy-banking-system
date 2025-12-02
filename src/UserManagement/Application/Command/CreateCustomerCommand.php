<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

final readonly class CreateCustomerCommand
{
    /**
     * @param list<array{street: string, city: string, postalCode: string, country: string}> $correspondenceAddresses
     */
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password,
        public string $firstName,
        public string $lastName,
        public string $permanentResidenceStreet,
        public string $permanentResidenceCity,
        public string $permanentResidencePostalCode,
        public string $permanentResidenceCountry,
        public array $correspondenceAddresses,
    ) {
    }
}
