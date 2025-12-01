<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class DebitCardNotFoundException extends NotFoundException
{
    public static function withCardNumber(string $cardNumber): self
    {
        return new self(\sprintf('Debit card with number "%s" not found', $cardNumber));
    }

    public static function generic(): self
    {
        return new self('Debit card not found');
    }
}
