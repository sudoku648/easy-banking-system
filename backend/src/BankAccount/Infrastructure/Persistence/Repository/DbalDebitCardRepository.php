<?php

declare(strict_types=1);

namespace App\BankAccount\Infrastructure\Persistence\Repository;

use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final readonly class DbalDebitCardRepository implements DebitCardRepositoryInterface
{
    private const string CARD_BIN = '4532'; // Visa BIN prefix

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(DebitCard $debitCard): void
    {
        $data = [
            'id' => $debitCard->id->getValue(),
            'card_number' => $debitCard->cardNumber->getValue(),
            'bank_account_id' => $debitCard->bankAccountId->getValue(),
            'is_active' => $debitCard->isActive,
            'issued_at' => $debitCard->issuedAt,
            'blocked_at' => $debitCard->blockedAt,
        ];

        $types = [
            'is_active' => Types::BOOLEAN,
            'issued_at' => Types::DATETIME_IMMUTABLE,
            'blocked_at' => Types::DATETIME_IMMUTABLE,
        ];

        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM debit_card WHERE id = :id',
            ['id' => $debitCard->id->getValue()],
        );

        if ($exists) {
            $this->connection->update('debit_card', $data, ['id' => $debitCard->id->getValue()], $types);
        } else {
            $this->connection->insert('debit_card', $data, $types);
        }
    }

    public function findById(DebitCardId $id): ?DebitCard
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM debit_card WHERE id = :id',
            ['id' => $id->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByCardNumber(DebitCardNumber $cardNumber): ?DebitCard
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM debit_card WHERE card_number = :card_number',
            ['card_number' => $cardNumber->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM debit_card WHERE bank_account_id = :bank_account_id ORDER BY issued_at DESC',
            ['bank_account_id' => $bankAccountId->getValue()],
        );

        return array_map(fn (array $data): DebitCard => $this->mapToEntity($data), $rows);
    }

    public function existsByCardNumber(DebitCardNumber $cardNumber): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM debit_card WHERE card_number = :card_number',
            ['card_number' => $cardNumber->getValue()],
        );

        return $count > 0;
    }

    public function nextIdentity(): DebitCardId
    {
        return DebitCardId::generate();
    }

    public function generateCardNumber(): DebitCardNumber
    {
        // Generate a unique 16-digit card number
        // Format: BIN (4 digits) + ACCOUNT (11 digits) + CHECK_DIGIT (1 digit)
        do {
            $accountPart = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
            $cardNumberWithoutCheck = self::CARD_BIN . $accountPart;
            $checkDigit = $this->calculateLuhnCheckDigit($cardNumberWithoutCheck);
            $cardNumber = $cardNumberWithoutCheck . $checkDigit;

            $debitCardNumber = DebitCardNumber::fromString($cardNumber);
        } while ($this->existsByCardNumber($debitCardNumber));

        return $debitCardNumber;
    }

    /**
     * Calculate Luhn check digit for card number validation.
     */
    private function calculateLuhnCheckDigit(string $number): int
    {
        $sum = 0;
        $reverse = strrev($number);

        for ($i = 0, $length = strlen($reverse); $i < $length; $i++) {
            $digit = (int) $reverse[$i];

            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * @param array{
     *   id: string,
     *   card_number: string,
     *   bank_account_id: string,
     *   is_active: bool,
     *   issued_at: string,
     *   blocked_at: ?string,
     * } $data
     */
    private function mapToEntity(array $data): DebitCard
    {
        return DebitCard::fromRaw($data);
    }
}
