<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Persistence\Repository;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DbalTransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(Transaction $transaction): void
    {
        $data = [
            'id' => $transaction->id->getValue(),
            'type' => $transaction->type->value,
            'bank_account_id' => $transaction->bankAccountId->getValue(),
            'amount' => $transaction->amount->getAmount(),
            'currency' => $transaction->amount->getCurrency()->value,
            'original_amount' => $transaction->originalAmount->getAmount(),
            'original_currency' => $transaction->originalAmount->getCurrency()->value,
            'exchange_rate' => $transaction->exchangeRate->getRate(),
            'occurred_at' => $transaction->occurredAt->format('Y-m-d H:i:s.uP'),
            'status' => $transaction->status->value,
        ];

        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM transaction WHERE id = :id',
            ['id' => $transaction->id->getValue()],
        );

        if ($exists) {
            $this->connection->update('transaction', $data, ['id' => $transaction->id->getValue()]);
        } else {
            $this->connection->insert('transaction', $data);
        }
    }

    public function findById(TransactionId $id): ?Transaction
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM transaction WHERE id = :id',
            ['id' => $id->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM transaction WHERE bank_account_id = :bank_account_id ORDER BY occurred_at DESC',
            ['bank_account_id' => $bankAccountId->getValue()],
        );

        return array_map(fn (array $data): Transaction => $this->mapToEntity($data), $rows);
    }

    public function findByBankAccountIds(array $bankAccountIds): array
    {
        if (empty($bankAccountIds)) {
            return [];
        }

        $ids = array_map(fn (BankAccountId $id): string => $id->getValue(), $bankAccountIds);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM transaction WHERE bank_account_id IN (:bank_account_ids) ORDER BY occurred_at DESC',
            ['bank_account_ids' => $ids],
            ['bank_account_ids' => ArrayParameterType::STRING],
        );

        return array_map(fn (array $data): Transaction => $this->mapToEntity($data), $rows);
    }

    public function findByBankAccountIdsPaginated(array $bankAccountIds, int $limit, int $offset): array
    {
        if (empty($bankAccountIds)) {
            return [];
        }

        $ids = array_map(fn (BankAccountId $id): string => $id->getValue(), $bankAccountIds);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM transaction WHERE bank_account_id IN (:bank_account_ids) ORDER BY occurred_at DESC LIMIT :limit OFFSET :offset',
            ['bank_account_ids' => $ids, 'limit' => $limit, 'offset' => $offset],
            ['bank_account_ids' => ArrayParameterType::STRING],
        );

        return array_map(fn (array $data): Transaction => $this->mapToEntity($data), $rows);
    }

    public function countByBankAccountIds(array $bankAccountIds): int
    {
        if (empty($bankAccountIds)) {
            return 0;
        }

        $ids = array_map(fn (BankAccountId $id): string => $id->getValue(), $bankAccountIds);

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM transaction WHERE bank_account_id IN (:bank_account_ids)',
            ['bank_account_ids' => $ids],
            ['bank_account_ids' => ArrayParameterType::STRING],
        );
    }

    public function findByBankAccountIdPaginated(BankAccountId $bankAccountId, int $limit, int $offset): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM transaction WHERE bank_account_id = :bank_account_id ORDER BY occurred_at DESC LIMIT :limit OFFSET :offset',
            ['bank_account_id' => $bankAccountId->getValue(), 'limit' => $limit, 'offset' => $offset],
        );

        return array_map(fn (array $data): Transaction => $this->mapToEntity($data), $rows);
    }

    public function countByBankAccountId(BankAccountId $bankAccountId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM transaction WHERE bank_account_id = :bank_account_id',
            ['bank_account_id' => $bankAccountId->getValue()],
        );
    }

    public function nextIdentity(): TransactionId
    {
        return TransactionId::generate();
    }

    /**
     * @param array{
     *   id: string,
     *   type: string,
     *   bank_account_id: string,
     *   amount: int,
     *   currency: string,
     *   original_amount: int,
     *   original_currency: string,
     *   exchange_rate: float,
     *   occurred_at: string,
     *   status: string,
     * } $data
     */
    private function mapToEntity(array $data): Transaction
    {
        return Transaction::fromRaw($data);
    }
}
