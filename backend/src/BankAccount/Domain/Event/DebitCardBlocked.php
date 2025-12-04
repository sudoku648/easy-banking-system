<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\Shared\Domain\Event\DomainEvent;

final class DebitCardBlocked extends DomainEvent
{
    private function __construct(
        public readonly DebitCardId $debitCardId,
    ) {
        parent::__construct();
    }

    public static function withData(
        DebitCardId $debitCardId,
    ): self {
        return new self($debitCardId);
    }
}
