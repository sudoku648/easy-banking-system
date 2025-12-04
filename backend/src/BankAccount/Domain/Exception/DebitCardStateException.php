<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class DebitCardStateException extends BusinessRuleViolationException
{
    public static function alreadyBlocked(): self
    {
        return new self('Card is already blocked');
    }

    public static function alreadyActive(): self
    {
        return new self('Card is already active');
    }

    public static function blockedOrInactive(): self
    {
        return new self('Debit card is blocked or inactive');
    }
}
