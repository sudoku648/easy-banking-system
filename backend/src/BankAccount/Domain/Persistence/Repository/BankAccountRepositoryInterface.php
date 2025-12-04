<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Persistence\Repository;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Iban;

interface BankAccountRepositoryInterface
{
    public function save(BankAccount $bankAccount): void;

    public function findById(BankAccountId $id): ?BankAccount;

    public function findByIban(Iban $iban): ?BankAccount;

    /**
     * @return BankAccount[]
     */
    public function findByCustomerId(CustomerId $customerId): array;

    /**
     * @return BankAccount[]
     */
    public function findAllActive(): array;

    public function existsByIban(Iban $iban): bool;

    public function nextIdentity(): BankAccountId;

    public function nextAccountNumber(): string;
}
