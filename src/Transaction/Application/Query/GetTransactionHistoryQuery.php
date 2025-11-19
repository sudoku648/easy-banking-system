<?php

declare(strict_types=1);

namespace App\Transaction\Application\Query;

final readonly class GetTransactionHistoryQuery
{
    public function __construct(
        public string $bankAccountId,
    ) {
    }
}
