<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

final readonly class WithdrawCashFromAtmCommand
{
    public function __construct(
        public string $cardNumber,
        public int $amount,
        public string $currency,
    ) {
    }
}
