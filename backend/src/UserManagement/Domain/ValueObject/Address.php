<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\ValueObject;

final readonly class Address implements ValueObject
{
    public function __construct(
        public Street $street,
        public City $city,
        public PostalCode $postalCode,
        public Country $country,
    ) {
    }

    /**
     * @param array{
     *     street: string,
     *     city: string,
     *     postal_code: string,
     *     country: string,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            Street::fromString($data['street']),
            City::fromString($data['city']),
            PostalCode::fromString($data['postal_code']),
            Country::fromString($data['country']),
        );
    }

    public function getValue(): string
    {
        return \sprintf(
            '%s, %s %s, %s',
            $this->street->getValue(),
            $this->postalCode->getValue(),
            $this->city->getValue(),
            $this->country->getValue(),
        );
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->street->equals($other->street)
            && $this->city->equals($other->city)
            && $this->postalCode->equals($other->postalCode)
            && $this->country->equals($other->country);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
