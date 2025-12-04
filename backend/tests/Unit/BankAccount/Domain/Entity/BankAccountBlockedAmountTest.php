<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Domain\Entity;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Exception\BankAccountStateException;
use App\BankAccount\Domain\Exception\InsufficientFundsException;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class BankAccountBlockedAmountTest extends TestCase
{
    private BankAccount $account;

    protected function setUp(): void
    {
        $this->account = BankAccount::open(
            BankAccountId::generate(),
            Iban::fromString('PL61109010140000071219812874'),
            CustomerId::generate(),
            new Money(10000, Currency::PLN), // 100.00 PLN
        );
    }

    public function testBlockAmountReducesAvailableBalance(): void
    {
        $amountToBlock = new Money(3000, Currency::PLN); // 30.00 PLN

        $this->account->blockAmount($amountToBlock);

        self::assertEquals(10000, $this->account->balance->getAmount());
        self::assertEquals(3000, $this->account->blockedAmount->getAmount());
        self::assertEquals(7000, $this->account->getAvailableBalance()->getAmount());
    }

    public function testBlockAmountFailsWhenInsufficientFunds(): void
    {
        $this->expectException(InsufficientFundsException::class);

        $amountToBlock = new Money(15000, Currency::PLN); // 150.00 PLN (more than balance)

        $this->account->blockAmount($amountToBlock);
    }

    public function testBlockAmountConsidersAlreadyBlockedAmount(): void
    {
        $this->account->blockAmount(new Money(6000, Currency::PLN)); // Block 60.00 PLN

        $this->expectException(InsufficientFundsException::class);

        // Try to block another 50.00 PLN - should fail as only 40.00 is available
        $this->account->blockAmount(new Money(5000, Currency::PLN));
    }

    public function testUnblockAmountIncreasesAvailableBalance(): void
    {
        $this->account->blockAmount(new Money(3000, Currency::PLN));
        $this->account->unblockAmount(new Money(1000, Currency::PLN));

        self::assertEquals(10000, $this->account->balance->getAmount());
        self::assertEquals(2000, $this->account->blockedAmount->getAmount());
        self::assertEquals(8000, $this->account->getAvailableBalance()->getAmount());
    }

    public function testUnblockAmountFailsWhenExceedingBlockedAmount(): void
    {
        $this->account->blockAmount(new Money(3000, Currency::PLN));

        $this->expectException(BankAccountStateException::class);

        $this->account->unblockAmount(new Money(4000, Currency::PLN));
    }

    public function testWithdrawReducesBothBalanceAndBlockedAmount(): void
    {
        $this->account->blockAmount(new Money(3000, Currency::PLN));
        $this->account->withdraw(new Money(3000, Currency::PLN));

        self::assertEquals(7000, $this->account->balance->getAmount());
        self::assertEquals(3000, $this->account->blockedAmount->getAmount());
        self::assertEquals(4000, $this->account->getAvailableBalance()->getAmount());
    }
}
