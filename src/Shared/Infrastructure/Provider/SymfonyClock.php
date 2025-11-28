<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Provider;

use App\Shared\Domain\Provider\ClockInterface;
use Symfony\Component\Clock\ClockInterface as SymfonyClockInterface;

final readonly class SymfonyClock implements ClockInterface
{
    public function __construct(
        private SymfonyClockInterface $clock,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }
}
