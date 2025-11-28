<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;

final class InMemoryDebitCardRepository implements DebitCardRepositoryInterface
{
    /** @var array<string, DebitCard> */
    private array $cards = [];

    public function save(DebitCard $debitCard): void
    {
        $this->cards[$debitCard->id->getValue()] = $debitCard;
    }

    public function findById(DebitCardId $id): ?DebitCard
    {
        return $this->cards[$id->getValue()] ?? null;
    }

    public function findByCardNumber(DebitCardNumber $cardNumber): ?DebitCard
    {
        foreach ($this->cards as $card) {
            if ($card->cardNumber->equals($cardNumber)) {
                return $card;
            }
        }

        return null;
    }

    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        return array_values(array_filter(
            $this->cards,
            static fn (DebitCard $card): bool => $card->bankAccountId->equals($bankAccountId),
        ));
    }

    public function generateCardNumber(): DebitCardNumber
    {
        // Generate simple sequential card numbers for testing
        $count = \count($this->cards) + 1;
        $cardNumber = '4532' . str_pad((string) $count, 12, '0', STR_PAD_LEFT);

        return DebitCardNumber::fromString($cardNumber);
    }

    public function existsByCardNumber(DebitCardNumber $cardNumber): bool
    {
        return $this->findByCardNumber($cardNumber) !== null;
    }

    public function nextIdentity(): DebitCardId
    {
        return DebitCardId::generate();
    }

    public function clear(): void
    {
        $this->cards = [];
    }
}
