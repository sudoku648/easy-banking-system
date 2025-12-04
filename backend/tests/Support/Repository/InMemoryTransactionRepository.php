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
        $this->transactions[$transaction->id->getValue()] = $transaction;
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
                fn (Transaction $transaction): bool => $transaction->bankAccountId->equals($bankAccountId),
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

    /**
     * @param BankAccountId[] $bankAccountIds
     * @return Transaction[]
     */
    public function findByBankAccountIdsPaginated(array $bankAccountIds, int $limit, int $offset): array
    {
        $all = $this->findByBankAccountIds($bankAccountIds);

        // Sort by occurred_at DESC (newest first)
        usort($all, fn (Transaction $a, Transaction $b): int => $b->occurredAt <=> $a->occurredAt);

        return \array_slice($all, $offset, $limit);
    }

    /**
     * @param BankAccountId[] $bankAccountIds
     */
    public function countByBankAccountIds(array $bankAccountIds): int
    {
        return \count($this->findByBankAccountIds($bankAccountIds));
    }

    public function findByBankAccountIdPaginated(BankAccountId $bankAccountId, int $limit, int $offset): array
    {
        $all = $this->findByBankAccountId($bankAccountId);

        // Sort by occurred_at DESC (newest first)
        usort($all, fn (Transaction $a, Transaction $b): int => $b->occurredAt <=> $a->occurredAt);

        return \array_slice($all, $offset, $limit);
    }

    public function countByBankAccountId(BankAccountId $bankAccountId): int
    {
        return \count($this->findByBankAccountId($bankAccountId));
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
