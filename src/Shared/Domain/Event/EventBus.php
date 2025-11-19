<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface EventBus
{
    public function dispatch(DomainEvent $event): void;
}
