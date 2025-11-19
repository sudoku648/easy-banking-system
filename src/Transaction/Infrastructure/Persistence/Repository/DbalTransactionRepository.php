<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Persistence\Repository;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\ExchangeRate;
use App\Transaction\Domain\ValueObject\TransactionId;
use App\Transaction\Domain\ValueObject\TransactionType;
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
            'id' => $transaction->getId()->getValue(),
            'type' => $transaction->getType()->value,
            'bank_account_id' => $transaction->getBankAccountId()->getValue(),
            'amount' => $transaction->getAmount()->getAmount(),
            'currency' => $transaction->getAmount()->getCurrency()->value,
            'original_amount' => $transaction->getOriginalAmount()->getAmount(),
            'original_currency' => $transaction->getOriginalAmount()->getCurrency()->value,
            'exchange_rate' => $transaction->getExchangeRate()->getRate(),
            'occurred_at' => $transaction->getOccurredAt()->format('Y-m-d H:i:s.uP'),
        ];

        $this->connection->insert('transaction', $data);
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
     * } $data
     */
    private function mapToEntity(array $data): Transaction
    {
        $amount = new Money((int) $data['amount'], Currency::fromString($data['currency']));
        $originalAmount = new Money((int) $data['original_amount'], Currency::fromString($data['original_currency']));
        $exchangeRate = new ExchangeRate(
            Currency::fromString($data['original_currency']),
            Currency::fromString($data['currency']),
            (float) $data['exchange_rate'],
        );

        return new Transaction(
            new TransactionId($data['id']),
            TransactionType::fromString($data['type']),
            new BankAccountId($data['bank_account_id']),
            $amount,
            $originalAmount,
            $exchangeRate,
            new \DateTimeImmutable($data['occurred_at']),
        );
    }
}
