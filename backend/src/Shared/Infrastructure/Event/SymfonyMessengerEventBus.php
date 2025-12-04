<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventBus;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyMessengerEventBus implements EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
