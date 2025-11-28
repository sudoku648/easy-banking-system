<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Event;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;

final class BankAccountOpened extends DomainEvent
{
    private function __construct(
        public readonly BankAccountId $bankAccountId,
        public readonly Iban $iban,
        public readonly CustomerId $customerId,
        public readonly Currency $currency,
    ) {
        parent::__construct();
    }

    public static function withData(
        BankAccountId $bankAccountId,
        Iban $iban,
        CustomerId $customerId,
        Currency $currency,
    ): self {
        return new self($bankAccountId, $iban, $customerId, $currency);
    }
}
