<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Fixtures;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Infrastructure\Fixtures\AbstractFixture;

final class TransactionFixture extends AbstractFixture
{
    private const int MIN_TRANSACTIONS_PER_ACCOUNT = 3;
    private const int MAX_TRANSACTIONS_PER_ACCOUNT = 15;
    private const int MAX_DAYS_BACK = 90;

    public function load(): void
    {
        echo "Loading transactions...\n";

        // Get all active bank accounts
        /** @var array<int, array{id: string, balance: int, currency: string}> $accounts */
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
                    'ATM_WITHDRAWAL',
                ]);

                // Generate transaction amount (in minor units)
                $amount = $this->faker->numberBetween(100, 50000); // 1.00 to 500.00 in major units

                // Generate occurred_at within the last MAX_DAYS_BACK days
                $occurredAt = $this->faker->dateTimeBetween(
                    \sprintf('-%d days', self::MAX_DAYS_BACK),
                    'now',
                )->format('Y-m-d H:i:s.uP');

                // Randomly decide if this transaction involves currency exchange
                // 40% chance for transfers, 10% chance for cash operations
                $shouldUseDifferentCurrency = $this->faker->boolean(
                    \in_array($type, ['TRANSFER_DEPOSIT', 'TRANSFER_WITHDRAWAL', 'ATM_WITHDRAWAL'], true) ? 40 : 10,
                );

                if ($shouldUseDifferentCurrency) {
                    // Use different currency for original amount
                    $originalCurrency = $currency->value === 'PLN' ? Currency::EUR : Currency::PLN;
                    // Generate realistic exchange rate (PLN/EUR typically between 4.0 and 4.8)
                    if ($originalCurrency->value === 'PLN') {
                        $exchangeRate = $this->faker->randomFloat(4, 4.0, 4.8); // EUR -> PLN
                        $originalAmount = (int) \round($amount / $exchangeRate);
                    } else {
                        $exchangeRate = $this->faker->randomFloat(4, 0.208, 0.250); // PLN -> EUR
                        $originalAmount = (int) \round($amount / $exchangeRate);
                    }
                } else {
                    // Same currency, no exchange
                    $originalCurrency = $currency;
                    $originalAmount = $amount;
                    $exchangeRate = 1.0;
                }

                $this->connection->insert('transaction', [
                    'id' => $this->faker->uuid(),
                    'type' => $type,
                    'bank_account_id' => $account['id'],
                    'amount' => $amount,
                    'currency' => $currency->value,
                    'original_amount' => $originalAmount,
                    'original_currency' => $originalCurrency->value,
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
