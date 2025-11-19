<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Money implements ValueObject
{
    /**
     * @param int $amount Amount in minor units (e.g., cents for PLN/EUR)
     */
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {
        Assert::greaterThanEq($this->amount, 0, 'Money amount must be non-negative');
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return array{amount: int, currency: string}
     */
    public function getValue(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        Assert::greaterThanEq(
            $this->amount,
            $other->amount,
            'Cannot subtract: insufficient funds',
        );

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    private function assertSameCurrency(self $other): void
    {
        Assert::true(
            $this->currency === $other->currency,
            \sprintf(
                'Cannot perform operation on different currencies: %s and %s',
                $this->currency->value,
                $other->currency->value,
            ),
        );
    }

    public function __toString(): string
    {
        return \sprintf('%d %s', $this->amount, $this->currency->value);
    }
}
