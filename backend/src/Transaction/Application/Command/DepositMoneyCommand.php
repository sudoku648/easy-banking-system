<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

final readonly class DepositMoneyCommand
{
    public function __construct(
        public string $bankAccountId,
        public int $amount,
        public string $currency,
    ) {
    }
}
