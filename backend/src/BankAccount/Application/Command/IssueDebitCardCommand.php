<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

final readonly class IssueDebitCardCommand
{
    public function __construct(
        public string $bankAccountId,
    ) {
    }
}
