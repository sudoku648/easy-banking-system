<?php

declare(strict_types=1);

namespace App\Shared\Domain\Provider;

use App\Shared\Domain\ValueObject\Iban;

interface IbanProviderInterface
{
    /**
     * Check if IBAN belongs to our bank
     */
    public function isInternalIban(Iban $iban): bool;

    /**
     * Get our bank's code
     */
    public function getBankCode(): string;
}
