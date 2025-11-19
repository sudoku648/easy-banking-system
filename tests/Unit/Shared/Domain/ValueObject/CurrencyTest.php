<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function testFromStringCreatesValidCurrency(): void
    {
        $currency = Currency::fromString('PLN');

        self::assertSame(Currency::PLN, $currency);
    }

    public function testFromStringThrowsExceptionForInvalidCurrency(): void
    {
        $this->expectException(\ValueError::class);

        Currency::fromString('USD');
    }

    public function testEqualsReturnsTrueForSameCurrency(): void
    {
        $currency1 = Currency::PLN;
        $currency2 = Currency::PLN;

        self::assertTrue($currency1->equals($currency2));
    }

    public function testEqualsReturnsFalseForDifferentCurrencies(): void
    {
        $currency1 = Currency::PLN;
        $currency2 = Currency::EUR;

        self::assertFalse($currency1->equals($currency2));
    }

    public function testCurrencyHasCorrectValue(): void
    {
        self::assertSame('PLN', Currency::PLN->value);
        self::assertSame('EUR', Currency::EUR->value);
    }
}
