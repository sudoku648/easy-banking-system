<?php

declare(strict_types=1);

namespace App\Shared\Domain\Provider;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
