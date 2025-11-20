<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

final readonly class FixtureLoader
{
    /**
     * @param iterable<FixtureInterface> $fixtures
     */
    public function __construct(
        private iterable $fixtures,
    ) {
    }

    public function load(): void
    {
        $orderedFixtures = $this->getOrderedFixtures();

        foreach ($orderedFixtures as $fixture) {
            $fixture->load();
        }
    }

    /**
     * @return array<FixtureInterface>
     */
    private function getOrderedFixtures(): array
    {
        $fixtures = [];

        foreach ($this->fixtures as $fixture) {
            $fixtures[] = $fixture;
        }

        usort($fixtures, fn (FixtureInterface $a, FixtureInterface $b) => $a->getOrder() <=> $b->getOrder());

        return $fixtures;
    }
}
