<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;

final readonly class BankAccountOpened implements DomainEvent
{
    public function __construct(
        public BankAccountId $bankAccountId,
        public Iban $iban,
        public CustomerId $customerId,
        public Currency $currency,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
