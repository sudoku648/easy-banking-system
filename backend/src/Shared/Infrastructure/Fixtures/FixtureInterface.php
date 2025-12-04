<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

interface FixtureInterface
{
    /**
     * Load fixtures into the database.
     */
    public function load(): void;

    /**
     * Get the order in which fixtures should be loaded.
     * Lower numbers are loaded first.
     */
    public function getOrder(): int;
}
