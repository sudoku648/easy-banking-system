<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class InvalidTransactionStateException extends BusinessRuleViolationException
{
    public static function cannotExecute(): self
    {
        return new self('Can only execute ordered transactions');
    }

    public static function cannotCancel(): self
    {
        return new self('Can only cancel ordered transactions');
    }

    public static function alreadyProcessed(): self
    {
        return new self('Transfer already processed');
    }
}
