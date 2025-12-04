<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

use Doctrine\DBAL\Connection;
use Faker\Factory;
use Faker\Generator;

abstract class AbstractFixture implements FixtureInterface
{
    protected readonly Generator $faker;

    public function __construct(
        protected readonly Connection $connection,
    ) {
        $this->faker = Factory::create();
    }
}
