<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Fixtures;

use App\Shared\Infrastructure\Fixtures\AbstractFixture;
use App\Shared\Domain\ValueObject\Currency;

final class TransactionFixture extends AbstractFixture
{
    private const int MIN_TRANSACTIONS_PER_ACCOUNT = 3;
    private const int MAX_TRANSACTIONS_PER_ACCOUNT = 15;
    private const int MAX_DAYS_BACK = 90;

    public function load(): void
    {
        echo "Loading transactions...\n";

        // Get all active bank accounts
        $accounts = $this->connection->fetchAllAssociative(
            'SELECT id, balance, currency FROM bank_account WHERE is_active = true ORDER BY id',
        );

        if (empty($accounts)) {
            echo "✗ No active bank accounts found, skipping transactions creation\n";
            return;
        }

        $totalTransactionsCreated = 0;

        foreach ($accounts as $account) {
            $transactionsCount = $this->faker->numberBetween(
                self::MIN_TRANSACTIONS_PER_ACCOUNT,
                self::MAX_TRANSACTIONS_PER_ACCOUNT,
            );

            $currency = Currency::fromString($account['currency']);

            for ($i = 0; $i < $transactionsCount; $i++) {
                $type = $this->faker->randomElement([
                    'CASH_DEPOSIT',
                    'CASH_WITHDRAWAL',
                    'TRANSFER_DEPOSIT',
                    'TRANSFER_WITHDRAWAL',
                ]);

                // Generate transaction amount (in minor units)
                $amount = $this->faker->numberBetween(100, 50000); // 1.00 to 500.00 in major units

                // Generate occurred_at within the last MAX_DAYS_BACK days
                $occurredAt = $this->faker->dateTimeBetween(
                    \sprintf('-%d days', self::MAX_DAYS_BACK),
                    'now',
                )->format('Y-m-d H:i:s.uP');

                // For simplicity, we'll use the same currency for original amount (no exchange)
                // In a real scenario, you might want to mix currencies for transfer transactions
                $exchangeRate = 1.0;

                $this->connection->insert('transaction', [
                    'id' => $this->faker->uuid(),
                    'type' => $type,
                    'bank_account_id' => $account['id'],
                    'amount' => $amount,
                    'currency' => $currency->value,
                    'original_amount' => $amount,
                    'original_currency' => $currency->value,
                    'exchange_rate' => $exchangeRate,
                    'occurred_at' => $occurredAt,
                ]);

                $totalTransactionsCreated++;
            }
        }

        echo \sprintf("✓ Created %d transactions for %d accounts\n", $totalTransactionsCreated, \count($accounts));
    }

    public function getOrder(): int
    {
        return 40;
    }
}
