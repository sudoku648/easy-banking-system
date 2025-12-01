<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\PendingTransferId;

final class InterbankTransferProcessed extends DomainEvent
{
    private function __construct(
        public readonly PendingTransferId $pendingTransferId,
        public readonly Iban $fromIban,
        public readonly Iban $toIban,
        public readonly Money $amount,
    ) {
        parent::__construct();
    }

    public static function withData(
        PendingTransferId $pendingTransferId,
        Iban $fromIban,
        Iban $toIban,
        Money $amount,
    ): self {
        return new self(
            $pendingTransferId,
            $fromIban,
            $toIban,
            $amount,
        );
    }
}
