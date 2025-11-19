<?php

declare(strict_types=1);

namespace App\Transaction\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\ValueObject;
use Webmozart\Assert\Assert;

final readonly class ExchangeRate implements ValueObject
{
    public function __construct(
        private Currency $fromCurrency,
        private Currency $toCurrency,
        private float $rate,
    ) {
        Assert::greaterThan($this->rate, 0, 'Exchange rate must be positive');
    }

    public static function identity(Currency $currency): self
    {
        return new self($currency, $currency, 1.0);
    }

    public function getFromCurrency(): Currency
    {
        return $this->fromCurrency;
    }

    public function getToCurrency(): Currency
    {
        return $this->toCurrency;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function convert(Money $money): Money
    {
        Assert::true(
            $money->getCurrency() === $this->fromCurrency,
            \sprintf(
                'Cannot convert: money currency %s does not match exchange rate from currency %s',
                $money->getCurrency()->value,
                $this->fromCurrency->value,
            ),
        );

        $convertedAmount = (int) round($money->getAmount() * $this->rate);

        return new Money($convertedAmount, $this->toCurrency);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->fromCurrency === $other->fromCurrency
            && $this->toCurrency === $other->toCurrency
            && abs($this->rate - $other->rate) < 0.000001;
    }

    /**
     * @return array{from: string, rate: float, to: string}
     */
    public function getValue(): array
    {
        return [
            'from' => $this->fromCurrency->value,
            'to' => $this->toCurrency->value,
            'rate' => $this->rate,
        ];
    }
}
