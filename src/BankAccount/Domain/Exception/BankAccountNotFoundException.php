<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class BankAccountNotFoundException extends NotFoundException
{
    public static function withId(string $accountId): self
    {
        return new self(\sprintf('Bank account with ID "%s" not found', $accountId));
    }

    public static function withIban(string $iban): self
    {
        return new self(\sprintf('Bank account with IBAN "%s" not found', $iban));
    }
}
