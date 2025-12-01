<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class BankAccountStateException extends BusinessRuleViolationException
{
    public static function cannotUnblockMoreThanBlocked(): self
    {
        return new self('Cannot unblock more than blocked amount');
    }

    public static function cannotCloseWithNonZeroBalance(): self
    {
        return new self('Cannot close account with non-zero balance');
    }
}
