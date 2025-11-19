<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\TransactionId;

final readonly class MoneyDeposited implements DomainEvent
{
    public function __construct(
        public TransactionId $transactionId,
        public Iban $iban,
        public Money $amount,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
