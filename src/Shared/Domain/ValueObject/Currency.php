<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

enum Currency: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
