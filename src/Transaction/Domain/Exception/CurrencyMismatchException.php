<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

final class CurrencyMismatchException extends ValidationException
{
    public static function forDeposit(): self
    {
        return new self('Deposit currency must match account currency');
    }
}
