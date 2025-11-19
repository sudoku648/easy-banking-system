<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Entity;

use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;

final class BankAccount
{
    public function __construct(
        private readonly BankAccountId $id,
        private readonly Iban $iban,
        private readonly CustomerId $customerId,
        private Money $balance,
        private bool $isActive = true,
    ) {
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

    public function getId(): BankAccountId
    {
        return $this->id;
    }

    public function getIban(): Iban
    {
        return $this->iban;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
