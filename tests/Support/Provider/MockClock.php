<?php

declare(strict_types=1);

namespace App\Tests\Support\Provider;

use App\Shared\Domain\Provider\ClockInterface;
use Symfony\Component\Clock\MockClock as SymfonyMockClock;

final class MockClock implements ClockInterface
{
    private SymfonyMockClock $clock;

    public function __construct()
    {
        $this->clock = new SymfonyMockClock();
    }

    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function sleep(float|int $seconds): void
    {
        $this->clock->sleep($seconds);
    }

    public function modify(string $modifier): void
    {
        $this->clock->modify($modifier);
    }

    public function withTimeZone(\DateTimeZone|string $timezone): static
    {
        $clone = clone $this;
        $newTime = $clone->clock->now()->setTimezone(
            \is_string($timezone) ? new \DateTimeZone($timezone) : $timezone,
        );
        $clone->clock = new SymfonyMockClock($newTime);

        return $clone;
    }
}
