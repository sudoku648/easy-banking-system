<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Money;

final readonly class BankAccountClosed implements DomainEvent
{
    public function __construct(
        public BankAccountId $bankAccountId,
        public Money $withdrawnBalance,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
