<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

abstract class UuidValueObject implements ValueObject
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        protected readonly string $value,
    ) {
        Assert::regex($this->value, self::UUID_PATTERN, 'Invalid UUID format');
    }

    public static function generate(): static
    {
        return new static(Uuid::v4()->toRfc4122());
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
