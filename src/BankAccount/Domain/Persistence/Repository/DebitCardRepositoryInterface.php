<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Persistence\Repository;

use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;

interface DebitCardRepositoryInterface
{
    public function save(DebitCard $debitCard): void;

    public function findById(DebitCardId $id): ?DebitCard;

    public function findByCardNumber(DebitCardNumber $cardNumber): ?DebitCard;

    /**
     * @return DebitCard[]
     */
    public function findByBankAccountId(BankAccountId $bankAccountId): array;

    public function existsByCardNumber(DebitCardNumber $cardNumber): bool;

    public function nextIdentity(): DebitCardId;

    public function generateCardNumber(): DebitCardNumber;
}
