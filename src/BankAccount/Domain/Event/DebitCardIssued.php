<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use App\Shared\Domain\Event\DomainEvent;

final class DebitCardIssued extends DomainEvent
{
    private function __construct(
        public readonly DebitCardId $debitCardId,
        public readonly DebitCardNumber $cardNumber,
        public readonly BankAccountId $bankAccountId,
    ) {
        parent::__construct();
    }

    public static function withData(
        DebitCardId $debitCardId,
        DebitCardNumber $cardNumber,
        BankAccountId $bankAccountId,
    ): self {
        return new self($debitCardId, $cardNumber, $bankAccountId);
    }
}
