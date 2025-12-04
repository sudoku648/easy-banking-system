<?php

declare(strict_types=1);

namespace App\Transaction\Domain\ValueObject;

enum TransactionStatus: string
{
    case ORDERED = 'ORDERED';
    case EXECUTED = 'EXECUTED';
    case CANCELED = 'CANCELED';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
