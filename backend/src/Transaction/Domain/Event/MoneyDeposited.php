<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\TransactionId;

final class MoneyDeposited extends DomainEvent
{
    private function __construct(
        public readonly TransactionId $transactionId,
        public readonly Iban $iban,
        public readonly Money $amount,
    ) {
        parent::__construct();
    }

    public static function withData(
        TransactionId $transactionId,
        Iban $iban,
        Money $amount,
    ): self {
        return new self($transactionId, $iban, $amount);
    }
}
