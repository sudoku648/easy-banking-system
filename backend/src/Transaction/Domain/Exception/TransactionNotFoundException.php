<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class TransactionNotFoundException extends NotFoundException
{
    public static function withId(string $transactionId): self
    {
        return new self(\sprintf('Transaction with ID "%s" not found', $transactionId));
    }
}
