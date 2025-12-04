<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Infrastructure\Provider;

use App\Shared\Domain\ValueObject\Currency;
use App\Transaction\Infrastructure\Provider\StaticExchangeRateProvider;
use PHPUnit\Framework\TestCase;

final class StaticExchangeRateProviderTest extends TestCase
{
    private StaticExchangeRateProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new StaticExchangeRateProvider();
    }

    public function testGetRateReturnsIdentityForSameCurrency(): void
    {
        $rate = $this->provider->getRate(Currency::PLN, Currency::PLN);

        self::assertSame(1.0, $rate->getRate());
        self::assertSame(Currency::PLN, $rate->getFromCurrency());
        self::assertSame(Currency::PLN, $rate->getToCurrency());
    }

    public function testGetRateFromPLNToEUR(): void
    {
        $rate = $this->provider->getRate(Currency::PLN, Currency::EUR);

        self::assertSame(Currency::PLN, $rate->getFromCurrency());
        self::assertSame(Currency::EUR, $rate->getToCurrency());
        self::assertEqualsWithDelta(0.2298, $rate->getRate(), 0.0001); // 1/4.35
    }

    public function testGetRateFromEURToPLN(): void
    {
        $rate = $this->provider->getRate(Currency::EUR, Currency::PLN);

        self::assertSame(Currency::EUR, $rate->getFromCurrency());
        self::assertSame(Currency::PLN, $rate->getToCurrency());
        self::assertSame(4.35, $rate->getRate());
    }

    public function testGetRateFromPLNToUSD(): void
    {
        $rate = $this->provider->getRate(Currency::PLN, Currency::USD);

        self::assertSame(Currency::PLN, $rate->getFromCurrency());
        self::assertSame(Currency::USD, $rate->getToCurrency());
        self::assertSame(0.25, $rate->getRate()); // 1/4.00
    }

    public function testGetRateFromUSDToPLN(): void
    {
        $rate = $this->provider->getRate(Currency::USD, Currency::PLN);

        self::assertSame(Currency::USD, $rate->getFromCurrency());
        self::assertSame(Currency::PLN, $rate->getToCurrency());
        self::assertSame(4.00, $rate->getRate());
    }

    public function testGetRateFromPLNToGBP(): void
    {
        $rate = $this->provider->getRate(Currency::PLN, Currency::GBP);

        self::assertSame(Currency::PLN, $rate->getFromCurrency());
        self::assertSame(Currency::GBP, $rate->getToCurrency());
        self::assertEqualsWithDelta(0.1923, $rate->getRate(), 0.0001); // 1/5.20
    }

    public function testGetRateFromGBPToPLN(): void
    {
        $rate = $this->provider->getRate(Currency::GBP, Currency::PLN);

        self::assertSame(Currency::GBP, $rate->getFromCurrency());
        self::assertSame(Currency::PLN, $rate->getToCurrency());
        self::assertSame(5.20, $rate->getRate());
    }

    public function testGetRateFromUSDToEURGoesthroughPLN(): void
    {
        // USD -> PLN -> EUR
        // 1 USD = 4.00 PLN, 1 EUR = 4.35 PLN
        // So 1 USD = 4.00 / 4.35 EUR ≈ 0.9195 EUR
        $rate = $this->provider->getRate(Currency::USD, Currency::EUR);

        self::assertSame(Currency::USD, $rate->getFromCurrency());
        self::assertSame(Currency::EUR, $rate->getToCurrency());
        self::assertEqualsWithDelta(0.9195, $rate->getRate(), 0.0001);
    }

    public function testGetRateFromEURToUSDGoesthroughPLN(): void
    {
        // EUR -> PLN -> USD
        // 1 EUR = 4.35 PLN, 1 USD = 4.00 PLN
        // So 1 EUR = 4.35 / 4.00 USD = 1.0875 USD
        $rate = $this->provider->getRate(Currency::EUR, Currency::USD);

        self::assertSame(Currency::EUR, $rate->getFromCurrency());
        self::assertSame(Currency::USD, $rate->getToCurrency());
        self::assertSame(1.0875, $rate->getRate());
    }

    public function testGetRateFromUSDToGBPGoesthroughPLN(): void
    {
        // USD -> PLN -> GBP
        // 1 USD = 4.00 PLN, 1 GBP = 5.20 PLN
        // So 1 USD = 4.00 / 5.20 GBP ≈ 0.7692 GBP
        $rate = $this->provider->getRate(Currency::USD, Currency::GBP);

        self::assertSame(Currency::USD, $rate->getFromCurrency());
        self::assertSame(Currency::GBP, $rate->getToCurrency());
        self::assertEqualsWithDelta(0.7692, $rate->getRate(), 0.0001);
    }

    public function testGetRateFromGBPToEURGoesthroughPLN(): void
    {
        // GBP -> PLN -> EUR
        // 1 GBP = 5.20 PLN, 1 EUR = 4.35 PLN
        // So 1 GBP = 5.20 / 4.35 EUR ≈ 1.1954 EUR
        $rate = $this->provider->getRate(Currency::GBP, Currency::EUR);

        self::assertSame(Currency::GBP, $rate->getFromCurrency());
        self::assertSame(Currency::EUR, $rate->getToCurrency());
        self::assertEqualsWithDelta(1.1954, $rate->getRate(), 0.0001);
    }
}
