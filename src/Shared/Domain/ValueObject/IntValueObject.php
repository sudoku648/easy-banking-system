<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class IntValueObject implements ValueObject
{
    protected function __construct(
        protected readonly int $value,
    ) {
    }

    final public static function fromInt(int $value): static
    {
        return new static($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
