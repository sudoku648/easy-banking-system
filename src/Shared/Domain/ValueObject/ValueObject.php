<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

interface ValueObject
{
    public function equals(self $other): bool;

    public function getValue(): mixed;
}
