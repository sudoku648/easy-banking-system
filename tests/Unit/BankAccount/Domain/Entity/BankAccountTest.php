<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Domain\Entity;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class BankAccountTest extends TestCase
{
    private BankAccountId $accountId;
    private Iban $iban;
    private CustomerId $customerId;

    protected function setUp(): void
    {
        $this->accountId = BankAccountId::generate();
        $this->iban = new Iban('PL61109010140000071219812874');
        $this->customerId = CustomerId::generate();
    }

    public function testOpenCreatesNewBankAccount(): void
    {
        $initialBalance = new Money(10000, Currency::PLN);

        $account = BankAccount::open(
            $this->accountId,
            $this->iban,
            $this->customerId,
            $initialBalance,
        );

        self::assertSame($this->accountId, $account->getId());
        self::assertSame($this->iban, $account->getIban());
        self::assertSame($this->customerId, $account->getCustomerId());
        self::assertTrue($account->getBalance()->equals($initialBalance));
        self::assertTrue($account->isActive());
    }

    public function testDepositIncreasesBalance(): void
    {
        $initialBalance = new Money(10000, Currency::PLN);
        $account = BankAccount::open($this->accountId, $this->iban, $this->customerId, $initialBalance);

        $depositAmount = new Money(5000, Currency::PLN);
        $account->deposit($depositAmount);

        $expectedBalance = new Money(15000, Currency::PLN);
        self::assertTrue($account->getBalance()->equals($expectedBalance));
    }

    public function testWithdrawDecreasesBalance(): void
    {
        $initialBalance = new Money(10000, Currency::PLN);
        $account = BankAccount::open($this->accountId, $this->iban, $this->customerId, $initialBalance);

        $withdrawAmount = new Money(3000, Currency::PLN);
        $account->withdraw($withdrawAmount);

        $expectedBalance = new Money(7000, Currency::PLN);
        self::assertTrue($account->getBalance()->equals($expectedBalance));
    }

    public function testWithdrawThrowsExceptionForInsufficientFunds(): void
    {
        $initialBalance = new Money(1000, Currency::PLN);
        $account = BankAccount::open($this->accountId, $this->iban, $this->customerId, $initialBalance);

        $withdrawAmount = new Money(2000, Currency::PLN);

        $this->expectException(InsufficientFundsException::class);
        $this->expectExceptionMessage('Insufficient funds in account');

        $account->withdraw($withdrawAmount);
    }

    public function testCloseDeactivatesAccountWithZeroBalance(): void
    {
        $zeroBalance = Money::zero(Currency::PLN);
        $account = BankAccount::open($this->accountId, $this->iban, $this->customerId, $zeroBalance);

        $account->close();

        self::assertFalse($account->isActive());
    }

    public function testCloseThrowsExceptionForNonZeroBalance(): void
    {
        $balance = new Money(1000, Currency::PLN);
        $account = BankAccount::open($this->accountId, $this->iban, $this->customerId, $balance);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot close account with non-zero balance');

        $account->close();
    }

    public function testHasOwnerReturnsTrueForCorrectCustomer(): void
    {
        $account = BankAccount::open(
            $this->accountId,
            $this->iban,
            $this->customerId,
            Money::zero(Currency::PLN),
        );

        self::assertTrue($account->hasOwner($this->customerId));
    }

    public function testHasOwnerReturnsFalseForDifferentCustomer(): void
    {
        $account = BankAccount::open(
            $this->accountId,
            $this->iban,
            $this->customerId,
            Money::zero(Currency::PLN),
        );

        $differentCustomerId = CustomerId::generate();

        self::assertFalse($account->hasOwner($differentCustomerId));
    }
}
