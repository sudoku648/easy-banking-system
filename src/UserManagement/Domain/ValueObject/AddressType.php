<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

enum AddressType: string
{
    case PERMANENT_RESIDENCE = 'PERMANENT_RESIDENCE';
    case CORRESPONDENCE = 'CORRESPONDENCE';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function isPermanentResidence(): bool
    {
        return $this === self::PERMANENT_RESIDENCE;
    }

    public function isCorrespondence(): bool
    {
        return $this === self::CORRESPONDENCE;
    }
}
