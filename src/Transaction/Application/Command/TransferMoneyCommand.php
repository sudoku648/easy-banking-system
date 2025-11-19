<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

final readonly class TransferMoneyCommand
{
    public function __construct(
        public string $fromBankAccountId,
        public string $toBankAccountId,
        public int $amount,
        public string $currency,
    ) {
    }
}
