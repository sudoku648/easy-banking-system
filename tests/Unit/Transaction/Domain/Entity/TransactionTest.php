<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Domain\Entity;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use App\Transaction\Domain\ValueObject\TransactionId;
use App\Transaction\Domain\ValueObject\TransactionType;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    private TransactionId $transactionId;
    private BankAccountId $bankAccountId;
    private \DateTimeImmutable $occurredAt;

    protected function setUp(): void
    {
        $this->transactionId = TransactionId::generate();
        $this->bankAccountId = BankAccountId::generate();
        $this->occurredAt = new \DateTimeImmutable('2025-11-19 12:00:00');
    }

    public function testCreateTransferWithdrawalCreatesValidTransaction(): void
    {
        $amount = new Money(10000, Currency::PLN);
        $originalAmount = new Money(10000, Currency::PLN);
        $exchangeRate = ExchangeRate::identity(Currency::PLN);

        $transaction = Transaction::createTransferWithdrawal(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $originalAmount,
            $exchangeRate,
            $this->occurredAt,
        );

        self::assertSame($this->transactionId, $transaction->id);
        self::assertSame(TransactionType::TRANSFER_WITHDRAWAL, $transaction->type);
        self::assertSame($this->bankAccountId, $transaction->bankAccountId);
        self::assertTrue($transaction->amount->equals($amount));
        self::assertTrue($transaction->originalAmount->equals($originalAmount));
        self::assertTrue($transaction->exchangeRate->equals($exchangeRate));
        self::assertSame($this->occurredAt, $transaction->occurredAt);
    }

    public function testCreateTransferDepositCreatesValidTransaction(): void
    {
        $amount = new Money(5000, Currency::EUR);
        $originalAmount = new Money(20000, Currency::PLN);
        $exchangeRate = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);

        $transaction = Transaction::createTransferDeposit(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $originalAmount,
            $exchangeRate,
            $this->occurredAt,
        );

        self::assertSame($this->transactionId, $transaction->id);
        self::assertSame(TransactionType::TRANSFER_DEPOSIT, $transaction->type);
        self::assertSame($this->bankAccountId, $transaction->bankAccountId);
        self::assertTrue($transaction->amount->equals($amount));
        self::assertTrue($transaction->originalAmount->equals($originalAmount));
        self::assertTrue($transaction->exchangeRate->equals($exchangeRate));
        self::assertSame($this->occurredAt, $transaction->occurredAt);
    }

    public function testCreateCashWithdrawalCreatesValidTransaction(): void
    {
        $amount = new Money(10000, Currency::PLN);

        $transaction = Transaction::createCashWithdrawal(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $this->occurredAt,
        );

        self::assertSame($this->transactionId, $transaction->id);
        self::assertSame(TransactionType::CASH_WITHDRAWAL, $transaction->type);
        self::assertSame($this->bankAccountId, $transaction->bankAccountId);
        self::assertTrue($transaction->amount->equals($amount));
        self::assertTrue($transaction->originalAmount->equals($amount));
        self::assertSame($this->occurredAt, $transaction->occurredAt);
    }

    public function testCashWithdrawalUsesIdentityExchangeRate(): void
    {
        $amount = new Money(10000, Currency::PLN);

        $transaction = Transaction::createCashWithdrawal(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $this->occurredAt,
        );

        $exchangeRate = $transaction->exchangeRate;
        self::assertSame(Currency::PLN, $exchangeRate->getFromCurrency());
        self::assertSame(Currency::PLN, $exchangeRate->getToCurrency());
        self::assertSame(1.0, $exchangeRate->getRate());
    }

    public function testCreateCashDepositCreatesValidTransaction(): void
    {
        $amount = new Money(15000, Currency::EUR);

        $transaction = Transaction::createCashDeposit(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $this->occurredAt,
        );

        self::assertSame($this->transactionId, $transaction->id);
        self::assertSame(TransactionType::CASH_DEPOSIT, $transaction->type);
        self::assertSame($this->bankAccountId, $transaction->bankAccountId);
        self::assertTrue($transaction->amount->equals($amount));
        self::assertTrue($transaction->originalAmount->equals($amount));
        self::assertSame($this->occurredAt, $transaction->occurredAt);
    }

    public function testCashDepositUsesIdentityExchangeRate(): void
    {
        $amount = new Money(15000, Currency::EUR);

        $transaction = Transaction::createCashDeposit(
            $this->transactionId,
            $this->bankAccountId,
            $amount,
            $this->occurredAt,
        );

        $exchangeRate = $transaction->exchangeRate;
        self::assertSame(Currency::EUR, $exchangeRate->getFromCurrency());
        self::assertSame(Currency::EUR, $exchangeRate->getToCurrency());
        self::assertSame(1.0, $exchangeRate->getRate());
    }
}
