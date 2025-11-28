<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

abstract class DomainEvent
{
    protected readonly \DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new \DateTimeImmutable();
    }

    final public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
