<?php

declare(strict_types=1);

namespace App\Transaction\Domain\ValueObject;

enum TransactionType: string
{
    case TRANSFER_WITHDRAWAL = 'TRANSFER_WITHDRAWAL';
    case TRANSFER_DEPOSIT = 'TRANSFER_DEPOSIT';
    case CASH_WITHDRAWAL = 'CASH_WITHDRAWAL';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
