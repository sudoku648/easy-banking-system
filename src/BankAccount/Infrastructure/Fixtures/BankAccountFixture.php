<?php

declare(strict_types=1);

namespace App\BankAccount\Infrastructure\Fixtures;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Infrastructure\Fixtures\AbstractFixture;

final class BankAccountFixture extends AbstractFixture
{
    private const string BANK_CODE = '10201026';
    private const int MIN_ACCOUNTS_PER_CUSTOMER = 1;
    private const int MAX_ACCOUNTS_PER_CUSTOMER = 3;

    public function load(): void
    {
        echo "Loading bank accounts...\n";

        // Get all customers
        $customers = $this->connection->fetchAllAssociative(
            'SELECT id FROM "user" WHERE role = :role ORDER BY id',
            ['role' => 'CUSTOMER'],
        );

        if (empty($customers)) {
            echo "✗ No customers found, skipping bank accounts creation\n";
            return;
        }

        $totalAccountsCreated = 0;

        foreach ($customers as $customer) {
            $accountsCount = $this->faker->numberBetween(
                self::MIN_ACCOUNTS_PER_CUSTOMER,
                self::MAX_ACCOUNTS_PER_CUSTOMER,
            );

            for ($i = 0; $i < $accountsCount; $i++) {
                /** @var Currency $currency */
                $currency = $this->faker->randomElement([Currency::PLN, Currency::EUR]);
                $balance = $this->faker->numberBetween(0, 100000) * 100; // 0 to 100,000 in major units

                $this->connection->insert('bank_account', [
                    'id' => $this->faker->uuid(),
                    'iban' => $this->generateUniqueIban(),
                    'customer_id' => $customer['id'],
                    'balance' => $balance,
                    'currency' => $currency->value,
                    'is_active' => $this->faker->boolean(95), // 95% active
                ], [
                    'is_active' => \Doctrine\DBAL\Types\Types::BOOLEAN,
                ]);

                $totalAccountsCreated++;
            }
        }

        echo \sprintf("✓ Created %d bank accounts for %d customers\n", $totalAccountsCreated, \count($customers));
    }

    public function getOrder(): int
    {
        return 30;
    }

    private function generateUniqueIban(): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $accountNumber = $this->generateAccountNumber();
            $iban = Iban::generatePolishIban($accountNumber);

            if (!$this->ibanExists($iban->getValue())) {
                return $iban->getValue();
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        throw new \RuntimeException('Could not generate unique IBAN after ' . $maxAttempts . ' attempts');
    }

    private function generateAccountNumber(): string
    {
        // Generate a unique 26-digit account number
        // Format: BANK_CODE (8 digits) + RANDOM (18 digits)
        $randomPart = str_pad((string) $this->faker->numberBetween(0, 999999999999999999), 18, '0', STR_PAD_LEFT);

        return self::BANK_CODE . $randomPart;
    }

    private function ibanExists(string $iban): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM bank_account WHERE iban = :iban',
            ['iban' => $iban],
        );

        return $count > 0;
    }
}
