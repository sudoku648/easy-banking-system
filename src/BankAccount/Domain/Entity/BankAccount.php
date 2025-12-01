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
        public private(set) Money $blockedAmount,
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
     *   blocked_amount: int,
     *   is_active: bool,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        $currency = Currency::from($data['currency']);
        return new self(
            BankAccountId::fromString($data['id']),
            Iban::fromString($data['iban']),
            CustomerId::fromString($data['customer_id']),
            new Money($data['balance'], $currency),
            new Money($data['blocked_amount'], $currency),
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
            Money::zero($initialBalance->getCurrency()),
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

    public function blockAmount(Money $amount): void
    {
        $availableBalance = $this->balance->subtract($this->blockedAmount);
        
        if (!$availableBalance->isGreaterThanOrEqual($amount)) {
            throw InsufficientFundsException::forAccount($this->id->getValue(), $amount);
        }

        $this->blockedAmount = $this->blockedAmount->add($amount);
    }

    public function unblockAmount(Money $amount): void
    {
        if (!$this->blockedAmount->isGreaterThanOrEqual($amount)) {
            throw new \DomainException('Cannot unblock more than blocked amount');
        }

        $this->blockedAmount = $this->blockedAmount->subtract($amount);
    }

    public function getAvailableBalance(): Money
    {
        return $this->balance->subtract($this->blockedAmount);
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
