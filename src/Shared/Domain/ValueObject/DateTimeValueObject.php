<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class DateTimeValueObject implements ValueObject
{
    public function __construct(
        protected readonly \DateTimeImmutable $value,
    ) {
    }

    public function getValue(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $this->value == $other->value;
    }

    public function __toString(): string
    {
        return $this->value->format(\DateTimeInterface::ATOM);
    }
}
