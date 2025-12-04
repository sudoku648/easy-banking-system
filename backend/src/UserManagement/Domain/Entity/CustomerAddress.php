<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Entity;

use App\UserManagement\Domain\ValueObject\Address;
use App\UserManagement\Domain\ValueObject\AddressId;
use App\UserManagement\Domain\ValueObject\AddressType;
use App\UserManagement\Domain\ValueObject\UserId;

final class CustomerAddress
{
    public function __construct(
        public readonly AddressId $id,
        public readonly UserId $customerId,
        public readonly AddressType $type,
        public private(set) Address $address,
    ) {
    }

    /**
     * @param array{
     *   id: string,
     *   customer_id: string,
     *   type: string,
     *   street: string,
     *   city: string,
     *   postal_code: string,
     *   country: string,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            AddressId::fromString($data['id']),
            UserId::fromString($data['customer_id']),
            AddressType::fromString($data['type']),
            Address::fromRaw([
                'street' => $data['street'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
            ]),
        );
    }

    public static function create(
        AddressId $id,
        UserId $customerId,
        AddressType $type,
        Address $address,
    ): self {
        return new self($id, $customerId, $type, $address);
    }
}
