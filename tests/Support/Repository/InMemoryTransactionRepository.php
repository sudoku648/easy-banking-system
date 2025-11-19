<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionId;

final class InMemoryTransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @var array<string, Transaction>
     */
    private array $transactions = [];

    public function save(Transaction $transaction): void
    {
        $this->transactions[$transaction->getId()->getValue()] = $transaction;
    }

    public function findById(TransactionId $id): ?Transaction
    {
        return $this->transactions[$id->getValue()] ?? null;
    }

    /**
     * @return Transaction[]
     */
    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        return array_values(
            array_filter(
                $this->transactions,
                fn (Transaction $transaction): bool => $transaction->getBankAccountId()->equals($bankAccountId),
            ),
        );
    }

    /**
     * @param BankAccountId[] $bankAccountIds
     * @return Transaction[]
     */
    public function findByBankAccountIds(array $bankAccountIds): array
    {
        $result = [];

        foreach ($bankAccountIds as $bankAccountId) {
            $result = array_merge($result, $this->findByBankAccountId($bankAccountId));
        }

        return $result;
    }

    public function nextIdentity(): TransactionId
    {
        return TransactionId::generate();
    }

    public function clear(): void
    {
        $this->transactions = [];
    }
}
