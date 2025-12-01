<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

final readonly class CancelInterbankTransferCommand
{
    public function __construct(
        public string $transactionId,
        public string $employeeId,
    ) {
    }
}
