<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

enum Locale: string
{
    case POLISH = 'pl';
    case ENGLISH = 'en';

    public function isPolish(): bool
    {
        return $this === self::POLISH;
    }

    public function isEnglish(): bool
    {
        return $this === self::ENGLISH;
    }

    /**
     * Create Locale from string value
     */
    public static function fromString(string $value): self
    {
        return self::tryFromString($value)
            ?? throw new \InvalidArgumentException(
                "Invalid locale: {$value}. Must be one of: " . implode(', ', self::codes()),
            );
    }

    /**
     * Try to create Locale from string value, returns null if invalid
     */
    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get all available locale codes
     * @return string[]
     */
    public static function codes(): array
    {
        return array_map(fn (self $locale): string => $locale->value, self::cases());
    }

    /**
     * Get default locale
     */
    public static function default(): self
    {
        return self::POLISH;
    }
}
