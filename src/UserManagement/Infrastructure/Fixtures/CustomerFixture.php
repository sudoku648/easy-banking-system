<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Fixtures;

use App\Shared\Infrastructure\Fixtures\AbstractFixture;
use App\UserManagement\Domain\ValueObject\HashedPassword;

final class CustomerFixture extends AbstractFixture
{
    private const int COUNT = 10;

    public function load(): void
    {
        echo "Loading customers...\n";

        $password = HashedPassword::fromPlainPassword('password123');

        // Create random customers
        for ($i = 0; $i < self::COUNT; $i++) {
            $firstName = $this->faker->firstName();
            $lastName = $this->faker->lastName();
            $username = strtolower(\sprintf(
                '%s.%s',
                $firstName,
                $lastName,
            ));

            // Ensure username uniqueness
            $username = $this->ensureUniqueUsername($username);

            $this->connection->insert('"user"', [
                'id' => $this->faker->uuid(),
                'username' => $username,
                'password' => $password->getValue(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => $this->faker->boolean(90), // 90% active
                'role' => 'CUSTOMER',
            ], [
                'is_active' => \Doctrine\DBAL\Types\Types::BOOLEAN,
            ]);
        }

        echo \sprintf("✓ Created %d customers\n", self::COUNT);
    }

    public function getOrder(): int
    {
        return 20;
    }

    private function ensureUniqueUsername(string $username): string
    {
        $originalUsername = $username;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }

    private function usernameExists(string $username): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM "user" WHERE username = :username',
            ['username' => $username],
        );

        return $count > 0;
    }
}
