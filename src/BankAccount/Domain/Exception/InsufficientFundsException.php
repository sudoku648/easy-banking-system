<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;
use App\Shared\Domain\ValueObject\Money;

final class InsufficientFundsException extends BusinessRuleViolationException
{
    public static function forAccount(string $accountId, Money $requestedAmount): self
    {
        return new self(
            \sprintf(
                'Insufficient funds in account %s. Requested amount: %s',
                $accountId,
                $requestedAmount,
            ),
        );
    }
}
