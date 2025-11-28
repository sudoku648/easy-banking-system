<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\Shared\Domain\Event\DomainEvent;

final readonly class DebitCardBlocked implements DomainEvent
{
    public function __construct(
        public DebitCardId $debitCardId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
