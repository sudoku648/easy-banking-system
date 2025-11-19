<?php

declare(strict_types=1);

namespace App\Tests\Support\Event;

use App\Shared\Domain\Event\EventBus;

final class InMemoryEventBus implements EventBus
{
    /**
     * @var list<object>
     */
    private array $dispatchedEvents = [];

    public function dispatch(object $event): void
    {
        $this->dispatchedEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * @template T of object
     * @param class-string<T> $eventClass
     * @return list<T>
     */
    public function getDispatchedEventsOfType(string $eventClass): array
    {
        return array_values(
            array_filter(
                $this->dispatchedEvents,
                fn (object $event): bool => $event instanceof $eventClass,
            ),
        );
    }

    public function clear(): void
    {
        $this->dispatchedEvents = [];
    }
}
