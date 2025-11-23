<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Entity;

use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;

final class BankAccount
{
    private function __construct(
        public readonly BankAccountId $id,
        public readonly Iban $iban,
        public readonly CustomerId $customerId,
        public private(set) Money $balance,
        public private(set) bool $isActive = true,
    ) {
    }

    /**
     * @param array{
     *   id: string,
     *   iban: string,
     *   customer_id: string,
     *   balance: int,
     *   currency: string,
     *   is_active: bool,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            BankAccountId::fromString($data['id']),
            Iban::fromString($data['iban']),
            CustomerId::fromString($data['customer_id']),
            new Money($data['balance'], Currency::from($data['currency'])),
            (bool) $data['is_active'],
        );
    }

    public static function open(
        BankAccountId $id,
        Iban $iban,
        CustomerId $customerId,
        Money $initialBalance,
    ): self {
        return new self(
            $id,
            $iban,
            $customerId,
            $initialBalance,
        );
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }

    public function withdraw(Money $amount): void
    {
        if (!$this->balance->isGreaterThanOrEqual($amount)) {
            throw InsufficientFundsException::forAccount($this->id->getValue(), $amount);
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function close(): void
    {
        if (!$this->balance->isZero()) {
            throw new \DomainException('Cannot close account with non-zero balance');
        }

        $this->isActive = false;
    }

    public function hasOwner(CustomerId $customerId): bool
    {
        return $this->customerId->equals($customerId);
    }
}
