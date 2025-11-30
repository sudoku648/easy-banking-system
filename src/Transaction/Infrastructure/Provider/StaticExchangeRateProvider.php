<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Provider;

use App\Shared\Domain\ValueObject\Currency;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\ExchangeRate;

final class StaticExchangeRateProvider implements ExchangeRateProviderInterface
{
    // @TODO Replace with real exchange rate service (e.g., NBP API)
    // PLN is the main currency - all exchanges go through PLN
    private const array BASE_RATES = [
        'EUR' => 4.35,  // 1 EUR = 4.35 PLN
        'USD' => 4.00,  // 1 USD = 4.00 PLN
        'GBP' => 5.20,  // 1 GBP = 5.20 PLN
    ];

    public function getRate(Currency $from, Currency $to): ExchangeRate
    {
        if ($from === $to) {
            return ExchangeRate::identity($from);
        }

        // If converting from PLN to another currency
        if ($from === Currency::PLN) {
            $rate = 1 / self::BASE_RATES[$to->value];

            return new ExchangeRate($from, $to, $rate);
        }

        // If converting to PLN from another currency
        if ($to === Currency::PLN) {
            $rate = self::BASE_RATES[$from->value];

            return new ExchangeRate($from, $to, $rate);
        }

        // For non-PLN to non-PLN exchanges, go through PLN
        // Example: USD -> EUR becomes USD -> PLN -> EUR
        // 1 USD = 4.00 PLN, 1 EUR = 4.35 PLN
        // So 1 USD = 4.00 / 4.35 EUR = 0.9195 EUR
        $fromToPln = self::BASE_RATES[$from->value];
        $toToPln = self::BASE_RATES[$to->value];
        $rate = $fromToPln / $toToPln;

        return new ExchangeRate($from, $to, $rate);
    }
}
