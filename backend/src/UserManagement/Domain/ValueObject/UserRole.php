<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

enum UserRole: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case CUSTOMER = 'CUSTOMER';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function isEmployee(): bool
    {
        return $this === self::EMPLOYEE;
    }

    public function isCustomer(): bool
    {
        return $this === self::CUSTOMER;
    }
}
