<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Entity;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use App\Transaction\Domain\ValueObject\TransactionId;
use App\Transaction\Domain\ValueObject\TransactionType;

final class Transaction
{
    private function __construct(
        public readonly TransactionId $id,
        public readonly TransactionType $type,
        public readonly BankAccountId $bankAccountId,
        public readonly Money $amount,
        public readonly Money $originalAmount,
        public readonly ExchangeRate $exchangeRate,
        public readonly \DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @param array{
     *   id: string,
     *   type: string,
     *   bank_account_id: string,
     *   amount: int,
     *   currency: string,
     *   original_amount: int,
     *   original_currency: string,
     *   exchange_rate: float,
     *   occurred_at: string,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            TransactionId::fromString($data['id']),
            TransactionType::from($data['type']),
            BankAccountId::fromString($data['bank_account_id']),
            new Money($data['amount'], Currency::from($data['currency'])),
            new Money($data['original_amount'], Currency::from($data['original_currency'])),
            new ExchangeRate(
                Currency::fromString($data['original_currency']),
                Currency::fromString($data['currency']),
                (float) $data['exchange_rate'],
            ),
            new \DateTimeImmutable($data['occurred_at']),
        );
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

    public static function createAtmWithdrawal(
        TransactionId $id,
        BankAccountId $bankAccountId,
        Money $amount,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            TransactionType::ATM_WITHDRAWAL,
            $bankAccountId,
            $amount,
            $amount,
            ExchangeRate::identity($amount->getCurrency()),
            $occurredAt,
        );
    }
}
