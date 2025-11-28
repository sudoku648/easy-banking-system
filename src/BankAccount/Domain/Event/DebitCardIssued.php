<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use App\Shared\Domain\Event\DomainEvent;

final readonly class DebitCardIssued implements DomainEvent
{
    public function __construct(
        public DebitCardId $debitCardId,
        public DebitCardNumber $cardNumber,
        public BankAccountId $bankAccountId,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
