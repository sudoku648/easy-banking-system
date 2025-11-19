<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class StringValueObject implements ValueObject
{
    public function __construct(
        protected readonly string $value,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
