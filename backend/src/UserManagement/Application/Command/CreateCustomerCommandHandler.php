<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Exception\UsernameAlreadyExistsException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\City;
use App\UserManagement\Domain\ValueObject\Country;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\PostalCode;
use App\UserManagement\Domain\ValueObject\Street;
use App\UserManagement\Domain\ValueObject\Username;

final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $username = Username::fromString($command->username);

        if ($this->userRepository->existsByUsername($username)) {
            throw UsernameAlreadyExistsException::forUsername($username->getValue());
        }

        $permanentResidenceAddress = new Address(
            Street::fromString($command->permanentResidenceStreet),
            City::fromString($command->permanentResidenceCity),
            PostalCode::fromString($command->permanentResidencePostalCode),
            Country::fromString($command->permanentResidenceCountry),
        );

        // Take first correspondence address for create() method
        $firstCorrespondence = $command->correspondenceAddresses[0] ?? throw new \InvalidArgumentException('At least one correspondence address is required');

        $firstCorrespondenceAddress = new Address(
            Street::fromString($firstCorrespondence['street']),
            City::fromString($firstCorrespondence['city']),
            PostalCode::fromString($firstCorrespondence['postalCode']),
            Country::fromString($firstCorrespondence['country']),
        );

        $customer = Customer::create(
            $this->userRepository->nextIdentity(),
            $username,
            HashedPassword::fromPlainPassword($command->password),
            FirstName::fromString($command->firstName),
            LastName::fromString($command->lastName),
            $permanentResidenceAddress,
            $firstCorrespondenceAddress,
        );

        // Add additional correspondence addresses
        for ($i = 1; $i < \count($command->correspondenceAddresses); $i++) {
            $addressData = $command->correspondenceAddresses[$i];
            $customer->addCorrespondenceAddress(
                new Address(
                    Street::fromString($addressData['street']),
                    City::fromString($addressData['city']),
                    PostalCode::fromString($addressData['postalCode']),
                    Country::fromString($addressData['country']),
                ),
            );
        }

        $this->userRepository->save($customer);
    }
}
