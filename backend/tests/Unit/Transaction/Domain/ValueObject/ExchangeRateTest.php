<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class ExchangeRateTest extends TestCase
{
    public function testConstructorCreatesValidExchangeRate(): void
    {
        $exchangeRate = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);

        self::assertSame(Currency::PLN, $exchangeRate->getFromCurrency());
        self::assertSame(Currency::EUR, $exchangeRate->getToCurrency());
        self::assertSame(0.25, $exchangeRate->getRate());
    }

    public function testConstructorThrowsExceptionForNegativeRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate must be positive');

        new ExchangeRate(Currency::PLN, Currency::EUR, -0.25);
    }

    public function testConstructorThrowsExceptionForZeroRate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exchange rate must be positive');

        new ExchangeRate(Currency::PLN, Currency::EUR, 0.0);
    }

    public function testIdentityCreatesExchangeRateOfOne(): void
    {
        $exchangeRate = ExchangeRate::identity(Currency::PLN);

        self::assertSame(Currency::PLN, $exchangeRate->getFromCurrency());
        self::assertSame(Currency::PLN, $exchangeRate->getToCurrency());
        self::assertSame(1.0, $exchangeRate->getRate());
    }

    public function testConvertConvertsMoneyToTargetCurrency(): void
    {
        $exchangeRate = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $money = new Money(10000, Currency::PLN);

        $converted = $exchangeRate->convert($money);

        self::assertSame(2500, $converted->getAmount());
        self::assertSame(Currency::EUR, $converted->getCurrency());
    }

    public function testConvertRoundsToNearestInteger(): void
    {
        $exchangeRate = new ExchangeRate(Currency::EUR, Currency::PLN, 4.33);
        $money = new Money(1000, Currency::EUR);

        $converted = $exchangeRate->convert($money);

        self::assertSame(4330, $converted->getAmount());
    }

    public function testConvertThrowsExceptionForMismatchedCurrency(): void
    {
        $exchangeRate = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $money = new Money(10000, Currency::EUR);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert: money currency EUR does not match exchange rate from currency PLN');

        $exchangeRate->convert($money);
    }

    public function testEqualsReturnsTrueForSameExchangeRate(): void
    {
        $exchangeRate1 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $exchangeRate2 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);

        self::assertTrue($exchangeRate1->equals($exchangeRate2));
    }

    public function testEqualsReturnsFalseForDifferentFromCurrency(): void
    {
        $exchangeRate1 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $exchangeRate2 = new ExchangeRate(Currency::EUR, Currency::EUR, 0.25);

        self::assertFalse($exchangeRate1->equals($exchangeRate2));
    }

    public function testEqualsReturnsFalseForDifferentToCurrency(): void
    {
        $exchangeRate1 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $exchangeRate2 = new ExchangeRate(Currency::PLN, Currency::PLN, 0.25);

        self::assertFalse($exchangeRate1->equals($exchangeRate2));
    }

    public function testEqualsReturnsFalseForDifferentRate(): void
    {
        $exchangeRate1 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);
        $exchangeRate2 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.26);

        self::assertFalse($exchangeRate1->equals($exchangeRate2));
    }

    public function testEqualsReturnsTrueForVeryCloseRates(): void
    {
        $exchangeRate1 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.250000001);
        $exchangeRate2 = new ExchangeRate(Currency::PLN, Currency::EUR, 0.250000002);

        self::assertTrue($exchangeRate1->equals($exchangeRate2));
    }

    public function testGetValueReturnsArrayWithCurrenciesAndRate(): void
    {
        $exchangeRate = new ExchangeRate(Currency::PLN, Currency::EUR, 0.25);

        $value = $exchangeRate->getValue();

        self::assertSame([
            'from' => 'PLN',
            'to' => 'EUR',
            'rate' => 0.25,
        ], $value);
    }
}
