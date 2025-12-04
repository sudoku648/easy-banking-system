<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

final readonly class OpenBankAccountCommand
{
    public function __construct(
        public string $customerId,
        public string $currency,
    ) {
    }
}
