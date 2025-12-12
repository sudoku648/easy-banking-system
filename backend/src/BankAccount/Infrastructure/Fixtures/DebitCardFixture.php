<?php

declare(strict_types=1);

namespace App\BankAccount\Infrastructure\Fixtures;

use App\Shared\Infrastructure\Fixtures\AbstractFixture;
use Doctrine\DBAL\Types\Types;

final class DebitCardFixture extends AbstractFixture
{
    private const string CARD_BIN = '4532'; // Visa BIN prefix
    private const int CARD_NUMBER_LENGTH = 16;
    private const int PERCENTAGE_ACCOUNTS_WITH_CARDS = 70; // 70% of active accounts get a card
    private const int PERCENTAGE_ACTIVE_CARDS = 90; // 90% of cards remain active

    public function load(): void
    {
        echo "Loading debit cards...\n";

        // Get all active bank accounts
        $accounts = $this->connection->fetchAllAssociative(
            'SELECT id FROM bank_account WHERE is_active = true ORDER BY id',
        );

        if (empty($accounts)) {
            echo "✗ No active bank accounts found, skipping debit cards creation\n";
            return;
        }

        $totalCardsCreated = 0;
        $activeCardsCount = 0;
        $blockedCardsCount = 0;

        foreach ($accounts as $account) {
            // Not all accounts get a debit card
            if (!$this->faker->boolean(self::PERCENTAGE_ACCOUNTS_WITH_CARDS)) {
                continue;
            }

            $cardNumber = $this->generateUniqueCardNumber();
            $isActive = $this->faker->boolean(self::PERCENTAGE_ACTIVE_CARDS);

            // Generate issued_at date within the last 2 years
            $issuedAt = $this->faker->dateTimeBetween('-2 years', 'now');

            // If card is blocked, generate blocked_at date after issued_at
            $blockedAt = null;
            if (!$isActive) {
                $blockedAt = $this->faker->dateTimeBetween(
                    $issuedAt->format('Y-m-d H:i:s'),
                    'now',
                );
            }

            $this->connection->insert('debit_card', [
                'id' => $this->faker->uuid(),
                'card_number' => $cardNumber,
                'bank_account_id' => $account['id'],
                'is_active' => $isActive,
                'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
                'blocked_at' => $blockedAt?->format('Y-m-d H:i:s'),
            ], [
                'is_active' => Types::BOOLEAN,
            ]);

            ++$totalCardsCreated;
            if ($isActive) {
                ++$activeCardsCount;
            } else {
                ++$blockedCardsCount;
            }
        }

        echo \sprintf(
            "✓ Created %d debit cards (%d active, %d blocked) for %d accounts\n",
            $totalCardsCreated,
            $activeCardsCount,
            $blockedCardsCount,
            \count($accounts),
        );
    }

    public function getOrder(): int
    {
        return 35; // After BankAccounts (30), before Transactions (40)
    }

    private function generateUniqueCardNumber(): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $cardNumber = $this->generateCardNumber();

            if (!$this->cardNumberExists($cardNumber)) {
                return $cardNumber;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        throw new \RuntimeException('Could not generate unique card number after ' . $maxAttempts . ' attempts');
    }

    private function generateCardNumber(): string
    {
        // Generate card number: BIN (4 digits) + Account identifier (12 digits)
        // Format: 4532 XXXX XXXX XXXX (Visa format)
        $remainingDigits = self::CARD_NUMBER_LENGTH - \strlen(self::CARD_BIN);
        $randomPart = str_pad(
            (string) $this->faker->numberBetween(0, (int) (10 ** $remainingDigits) - 1),
            $remainingDigits,
            '0',
            STR_PAD_LEFT,
        );

        return self::CARD_BIN . $randomPart;
    }

    private function cardNumberExists(string $cardNumber): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM debit_card WHERE card_number = :cardNumber',
            ['cardNumber' => $cardNumber],
        );

        return $count > 0;
    }
}
