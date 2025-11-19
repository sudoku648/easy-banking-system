<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Exception;

final class IbanAlreadyExistsException extends \DomainException
{
    public static function forIban(string $iban): self
    {
        return new self(\sprintf('Bank account with IBAN "%s" already exists', $iban));
    }
}
