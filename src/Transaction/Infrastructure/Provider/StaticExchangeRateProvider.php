<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Provider;

use App\Shared\Domain\ValueObject\Currency;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\ExchangeRate;

final class StaticExchangeRateProvider implements ExchangeRateProviderInterface
{
    // @TODO Replace with real exchange rate service (e.g., NBP API)
    private const array RATES = [
        'PLN_EUR' => 0.23,
        'EUR_PLN' => 4.35,
    ];

    public function getRate(Currency $from, Currency $to): ExchangeRate
    {
        if ($from === $to) {
            return ExchangeRate::identity($from);
        }

        $key = \sprintf('%s_%s', $from->value, $to->value);
        $rate = self::RATES[$key] ?? null;

        if ($rate === null) {
            throw new \RuntimeException(
                \sprintf('Exchange rate not found for %s to %s', $from->value, $to->value),
            );
        }

        return new ExchangeRate($from, $to, $rate);
    }
}
