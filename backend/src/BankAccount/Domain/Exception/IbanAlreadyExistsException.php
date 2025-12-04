<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class IbanAlreadyExistsException extends ConflictException
{
    public static function forIban(string $iban): self
    {
        return new self(\sprintf('Bank account with IBAN "%s" already exists', $iban));
    }
}
