<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\Shared\Application\Command\AsyncCommandInterface;

final readonly class TransferMoneyCommand implements AsyncCommandInterface
{
    public function __construct(
        public string $fromBankAccountId,
        public string $toBankAccountId,
        public int $amount,
        public string $currency,
    ) {
    }
}
