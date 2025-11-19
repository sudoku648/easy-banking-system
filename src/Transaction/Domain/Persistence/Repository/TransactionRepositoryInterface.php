<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Persistence\Repository;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionId;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;

    public function findById(TransactionId $id): ?Transaction;

    /**
     * @return Transaction[]
     */
    public function findByBankAccountId(BankAccountId $bankAccountId): array;

    /**
     * @param BankAccountId[] $bankAccountIds
     * @return Transaction[]
     */
    public function findByBankAccountIds(array $bankAccountIds): array;

    public function nextIdentity(): TransactionId;
}
