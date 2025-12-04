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

            $customerId = $this->faker->uuid();

            $this->connection->insert('"user"', [
                'id' => $customerId,
                'username' => $username,
                'password' => $password->getValue(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => $this->faker->boolean(90), // 90% active
                'role' => 'CUSTOMER',
            ], [
                'is_active' => \Doctrine\DBAL\Types\Types::BOOLEAN,
            ]);

            $permanentStreet = $this->faker->streetAddress();
            $permanentCity = $this->faker->city();
            $permanentPostalCode = \sprintf('%02d-%03d', $this->faker->numberBetween(10, 99), $this->faker->numberBetween(100, 999));
            $permanentCountry = 'Poland';

            // Create permanent residence address
            $this->connection->insert('customer_address', [
                'id' => $this->faker->uuid(),
                'customer_id' => $customerId,
                'type' => 'PERMANENT_RESIDENCE',
                'street' => $permanentStreet,
                'city' => $permanentCity,
                'postal_code' => $permanentPostalCode,
                'country' => $permanentCountry,
            ]);

            // Create correspondence address - 60% same as permanent, 40% different
            if ($this->faker->boolean(60)) {
                // Same as permanent
                $this->connection->insert('customer_address', [
                    'id' => $this->faker->uuid(),
                    'customer_id' => $customerId,
                    'type' => 'CORRESPONDENCE',
                    'street' => $permanentStreet,
                    'city' => $permanentCity,
                    'postal_code' => $permanentPostalCode,
                    'country' => $permanentCountry,
                ]);
            } else {
                // Different address
                $this->connection->insert('customer_address', [
                    'id' => $this->faker->uuid(),
                    'customer_id' => $customerId,
                    'type' => 'CORRESPONDENCE',
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'postal_code' => \sprintf('%02d-%03d', $this->faker->numberBetween(10, 99), $this->faker->numberBetween(100, 999)),
                    'country' => 'Poland',
                ]);
            }
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
