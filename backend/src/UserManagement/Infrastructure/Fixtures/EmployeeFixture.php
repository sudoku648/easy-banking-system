<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Fixtures;

use App\Shared\Infrastructure\Fixtures\AbstractFixture;
use App\UserManagement\Domain\ValueObject\HashedPassword;

final class EmployeeFixture extends AbstractFixture
{
    private const int COUNT = 3;

    public function load(): void
    {
        echo "Loading employees...\n";

        $password = HashedPassword::fromPlainPassword('password123');

        // Create predefined employees
        $employees = [
            [
                'username' => 'john.smith',
                'firstName' => 'John',
                'lastName' => 'Smith',
            ],
            [
                'username' => 'anna.kowalska',
                'firstName' => 'Anna',
                'lastName' => 'Kowalska',
            ],
            [
                'username' => 'michael.brown',
                'firstName' => 'Michael',
                'lastName' => 'Brown',
            ],
        ];

        foreach ($employees as $employee) {
            $this->connection->insert('"user"', [
                'id' => $this->faker->uuid(),
                'username' => $employee['username'],
                'password' => $password->getValue(),
                'first_name' => $employee['firstName'],
                'last_name' => $employee['lastName'],
                'is_active' => true,
                'role' => 'EMPLOYEE',
            ], [
                'is_active' => \Doctrine\DBAL\Types\Types::BOOLEAN,
            ]);
        }

        echo \sprintf("✓ Created %d employees\n", self::COUNT);
    }

    public function getOrder(): int
    {
        return 10;
    }
}
