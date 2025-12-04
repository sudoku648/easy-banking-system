<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class NoAccountsFoundException extends NotFoundException
{
    public static function forCustomer(string $customerId): self
    {
        return new self(\sprintf('No accounts found for customer "%s"', $customerId));
    }

    public static function generic(): self
    {
        return new self('No accounts found for this customer');
    }
}
