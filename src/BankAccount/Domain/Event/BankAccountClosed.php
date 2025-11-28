<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Money;

final class BankAccountClosed extends DomainEvent
{
    private function __construct(
        public readonly BankAccountId $bankAccountId,
        public readonly Money $withdrawnBalance,
    ) {
        parent::__construct();
    }

    public static function withData(
        BankAccountId $bankAccountId,
        Money $withdrawnBalance,
    ): self {
        return new self($bankAccountId, $withdrawnBalance);
    }
}
