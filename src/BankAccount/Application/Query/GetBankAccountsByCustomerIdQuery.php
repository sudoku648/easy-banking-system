<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Query;

final readonly class GetBankAccountsByCustomerIdQuery
{
    public function __construct(
        public string $customerId,
    ) {
    }
}
