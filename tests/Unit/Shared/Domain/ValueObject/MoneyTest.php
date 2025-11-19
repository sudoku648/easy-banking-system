<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class MoneyTest extends TestCase
{
    public function testConstructorCreatesValidMoney(): void
    {
        $money = new Money(1000, Currency::PLN);

        self::assertSame(1000, $money->getAmount());
        self::assertSame(Currency::PLN, $money->getCurrency());
    }

    public function testConstructorThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money amount must be non-negative');

        new Money(-100, Currency::PLN);
    }

    public function testZeroCreatesMoneyWithZeroAmount(): void
    {
        $money = Money::zero(Currency::EUR);

        self::assertSame(0, $money->getAmount());
        self::assertSame(Currency::EUR, $money->getCurrency());
    }

    public function testGetValueReturnsArrayWithAmountAndCurrency(): void
    {
        $money = new Money(2500, Currency::PLN);

        $value = $money->getValue();

        self::assertSame(['amount' => 2500, 'currency' => 'PLN'], $value);
    }

    public function testEqualsReturnsTrueForSameMoneyValues(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(1000, Currency::PLN);

        self::assertTrue($money1->equals($money2));
    }

    public function testEqualsReturnsFalseForDifferentAmounts(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(2000, Currency::PLN);

        self::assertFalse($money1->equals($money2));
    }

    public function testEqualsReturnsFalseForDifferentCurrencies(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(1000, Currency::EUR);

        self::assertFalse($money1->equals($money2));
    }

    public function testAddReturnsSumOfTwoMoneyValues(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(500, Currency::PLN);

        $result = $money1->add($money2);

        self::assertSame(1500, $result->getAmount());
        self::assertSame(Currency::PLN, $result->getCurrency());
    }

    public function testAddThrowsExceptionForDifferentCurrencies(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(500, Currency::EUR);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot perform operation on different currencies: PLN and EUR');

        $money1->add($money2);
    }

    public function testSubtractReturnsMoneyDifference(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(300, Currency::PLN);

        $result = $money1->subtract($money2);

        self::assertSame(700, $result->getAmount());
        self::assertSame(Currency::PLN, $result->getCurrency());
    }

    public function testSubtractThrowsExceptionForInsufficientFunds(): void
    {
        $money1 = new Money(500, Currency::PLN);
        $money2 = new Money(1000, Currency::PLN);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract: insufficient funds');

        $money1->subtract($money2);
    }

    public function testSubtractThrowsExceptionForDifferentCurrencies(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(500, Currency::EUR);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot perform operation on different currencies: PLN and EUR');

        $money1->subtract($money2);
    }

    public function testIsGreaterThanReturnsTrueWhenAmountIsGreater(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(500, Currency::PLN);

        self::assertTrue($money1->isGreaterThan($money2));
        self::assertFalse($money2->isGreaterThan($money1));
    }

    public function testIsGreaterThanReturnsFalseForEqualAmounts(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(1000, Currency::PLN);

        self::assertFalse($money1->isGreaterThan($money2));
    }

    public function testIsGreaterThanOrEqualReturnsTrueWhenAmountIsGreaterOrEqual(): void
    {
        $money1 = new Money(1000, Currency::PLN);
        $money2 = new Money(500, Currency::PLN);
        $money3 = new Money(1000, Currency::PLN);

        self::assertTrue($money1->isGreaterThanOrEqual($money2));
        self::assertTrue($money1->isGreaterThanOrEqual($money3));
        self::assertFalse($money2->isGreaterThanOrEqual($money1));
    }

    public function testIsLessThanReturnsTrueWhenAmountIsLess(): void
    {
        $money1 = new Money(500, Currency::PLN);
        $money2 = new Money(1000, Currency::PLN);

        self::assertTrue($money1->isLessThan($money2));
        self::assertFalse($money2->isLessThan($money1));
    }

    public function testIsZeroReturnsTrueForZeroAmount(): void
    {
        $money = Money::zero(Currency::PLN);

        self::assertTrue($money->isZero());
    }

    public function testIsZeroReturnsFalseForNonZeroAmount(): void
    {
        $money = new Money(100, Currency::PLN);

        self::assertFalse($money->isZero());
    }

    public function testIsPositiveReturnsTrueForPositiveAmount(): void
    {
        $money = new Money(100, Currency::PLN);

        self::assertTrue($money->isPositive());
    }

    public function testIsPositiveReturnsFalseForZeroAmount(): void
    {
        $money = Money::zero(Currency::PLN);

        self::assertFalse($money->isPositive());
    }

    public function testToStringReturnsFormattedString(): void
    {
        $money = new Money(1000, Currency::PLN);

        self::assertSame('10,00 PLN', (string) $money);
    }

    public function testToStringReturnsFormattedStringWithZeroAmount(): void
    {
        $money = Money::zero(Currency::EUR);

        self::assertSame('0,00 EUR', (string) $money);
    }
}
