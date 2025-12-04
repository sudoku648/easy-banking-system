<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

final readonly class InterbankTransferCommand
{
    public function __construct(
        public string $fromBankAccountId,
        public string $toIban,
        public int $amount,
        public string $currency,
    ) {
    }
}
