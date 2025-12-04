<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Entity;

use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\AddressId;
use App\UserManagement\Domain\ValueObject\AddressType;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;

final class Customer extends User
{
    /**
     * @var CustomerAddress[]
     */
    private array $addresses = [];

    public static function create(
        UserId $id,
        Username $username,
        HashedPassword $password,
        FirstName $firstName,
        LastName $lastName,
        Address $permanentResidenceAddress,
        Address $correspondenceAddress,
    ): self {
        $customer = new self(
            $id,
            $username,
            $password,
            $firstName,
            $lastName,
        );

        $customer->addresses[] = CustomerAddress::create(
            AddressId::generate(),
            $id,
            AddressType::PERMANENT_RESIDENCE,
            $permanentResidenceAddress,
        );

        $customer->addresses[] = CustomerAddress::create(
            AddressId::generate(),
            $id,
            AddressType::CORRESPONDENCE,
            $correspondenceAddress,
        );

        return $customer;
    }

    public function getRole(): UserRole
    {
        return UserRole::CUSTOMER;
    }

    public function addCorrespondenceAddress(Address $address): void
    {
        $this->addresses[] = CustomerAddress::create(
            AddressId::generate(),
            $this->id,
            AddressType::CORRESPONDENCE,
            $address,
        );
    }

    public function getPermanentResidenceAddress(): ?CustomerAddress
    {
        foreach ($this->addresses as $address) {
            if ($address->type->isPermanentResidence()) {
                return $address;
            }
        }

        return null;
    }

    /**
     * @return CustomerAddress[]
     */
    public function getCorrespondenceAddresses(): array
    {
        return \array_filter(
            $this->addresses,
            fn (CustomerAddress $address): bool => $address->type->isCorrespondence(),
        );
    }

    /**
     * @return CustomerAddress[]
     */
    public function getAllAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @internal Used by repository to set addresses after loading from database
     * @param CustomerAddress[] $addresses
     */
    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
    }
}
