<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Entity;

use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use App\Transaction\Domain\ValueObject\TransactionId;
use App\Transaction\Domain\ValueObject\TransactionType;

final class Transaction
{
    public function __construct(
        private readonly TransactionId $id,
        private readonly TransactionType $type,
        private readonly BankAccountId $bankAccountId,
        private readonly Money $amount,
        private readonly Money $originalAmount,
        private readonly ExchangeRate $exchangeRate,
        private readonly \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function createTransferWithdrawal(
        TransactionId $id,
        BankAccountId $bankAccountId,
        Money $amount,
        Money $originalAmount,
        ExchangeRate $exchangeRate,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            TransactionType::TRANSFER_WITHDRAWAL,
            $bankAccountId,
            $amount,
            $originalAmount,
            $exchangeRate,
            $occurredAt,
        );
    }

    public static function createTransferDeposit(
        TransactionId $id,
        BankAccountId $bankAccountId,
        Money $amount,
        Money $originalAmount,
        ExchangeRate $exchangeRate,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            TransactionType::TRANSFER_DEPOSIT,
            $bankAccountId,
            $amount,
            $originalAmount,
            $exchangeRate,
            $occurredAt,
        );
    }

    public static function createCashWithdrawal(
        TransactionId $id,
        BankAccountId $bankAccountId,
        Money $amount,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            TransactionType::CASH_WITHDRAWAL,
            $bankAccountId,
            $amount,
            $amount,
            ExchangeRate::identity($amount->getCurrency()),
            $occurredAt,
        );
    }

    public static function createCashDeposit(
        TransactionId $id,
        BankAccountId $bankAccountId,
        Money $amount,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            TransactionType::CASH_DEPOSIT,
            $bankAccountId,
            $amount,
            $amount,
            ExchangeRate::identity($amount->getCurrency()),
            $occurredAt,
        );
    }

    public function getId(): TransactionId
    {
        return $this->id;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getBankAccountId(): BankAccountId
    {
        return $this->bankAccountId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getOriginalAmount(): Money
    {
        return $this->originalAmount;
    }

    public function getExchangeRate(): ExchangeRate
    {
        return $this->exchangeRate;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
