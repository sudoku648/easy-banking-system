<?php

declare(strict_types=1);

namespace App\Tests\Support\Provider;

use App\Shared\Domain\ValueObject\Currency;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\ExchangeRate;

final class MockExchangeRateProvider implements ExchangeRateProviderInterface
{
    /**
     * @var array<string, ExchangeRate>
     */
    private array $rates = [];

    public function getRate(Currency $from, Currency $to): ExchangeRate
    {
        if ($from === $to) {
            return ExchangeRate::identity($from);
        }

        $key = $this->generateKey($from, $to);

        if (!isset($this->rates[$key])) {
            throw new \RuntimeException(
                \sprintf('Exchange rate not configured for %s to %s', $from->value, $to->value),
            );
        }

        return $this->rates[$key];
    }

    public function setRate(Currency $from, Currency $to, float $rate): void
    {
        $key = $this->generateKey($from, $to);
        $this->rates[$key] = new ExchangeRate($from, $to, $rate);
    }

    public function clear(): void
    {
        $this->rates = [];
    }

    private function generateKey(Currency $from, Currency $to): string
    {
        return $from->value . '_' . $to->value;
    }
}
