<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Persistence\Repository;

use App\Transaction\Domain\Entity\PendingInterbankTransfer;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\PendingTransferId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final readonly class DbalPendingInterbankTransferRepository implements PendingInterbankTransferRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function nextIdentity(): PendingTransferId
    {
        return PendingTransferId::generate();
    }

    public function save(PendingInterbankTransfer $transfer): void
    {
        $exists = $this->findById($transfer->id) !== null;

        if ($exists) {
            $this->update($transfer);
        } else {
            $this->insert($transfer);
        }
    }

    public function findById(PendingTransferId $id): ?PendingInterbankTransfer
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM pending_interbank_transfer WHERE id = :id',
            ['id' => $id->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return PendingInterbankTransfer::fromRaw($data);
    }

    public function findPendingTransfers(): array
    {
        $results = $this->connection->fetchAllAssociative(
            'SELECT * FROM pending_interbank_transfer WHERE is_processed = false ORDER BY created_at ASC',
        );

        return array_map(
            fn (array $data): PendingInterbankTransfer => PendingInterbankTransfer::fromRaw($data),
            $results,
        );
    }

    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        $results = $this->connection->fetchAllAssociative(
            'SELECT * FROM pending_interbank_transfer WHERE from_bank_account_id = :bankAccountId ORDER BY created_at DESC',
            ['bankAccountId' => $bankAccountId->getValue()],
        );

        return array_map(
            fn (array $data): PendingInterbankTransfer => PendingInterbankTransfer::fromRaw($data),
            $results,
        );
    }

    private function insert(PendingInterbankTransfer $transfer): void
    {
        $this->connection->insert(
            'pending_interbank_transfer',
            [
                'id' => $transfer->id->getValue(),
                'from_bank_account_id' => $transfer->fromBankAccountId->getValue(),
                'to_iban' => $transfer->toIban->getValue(),
                'amount' => $transfer->amount->getAmount(),
                'currency' => $transfer->amount->getCurrency()->value,
                'created_at' => $transfer->createdAt,
                'is_processed' => $transfer->isProcessed,
                'processed_at' => $transfer->processedAt,
                'transaction_id' => $transfer->transactionId?->getValue(),
            ],
            [
                'is_processed' => Types::BOOLEAN,
                'created_at' => Types::DATETIME_IMMUTABLE,
                'processed_at' => Types::DATETIME_IMMUTABLE,
            ],
        );
    }

    private function update(PendingInterbankTransfer $transfer): void
    {
        $this->connection->update(
            'pending_interbank_transfer',
            [
                'is_processed' => $transfer->isProcessed,
                'processed_at' => $transfer->processedAt,
                'transaction_id' => $transfer->transactionId?->getValue(),
            ],
            ['id' => $transfer->id->getValue()],
            [
                'is_processed' => Types::BOOLEAN,
                'processed_at' => Types::DATETIME_IMMUTABLE,
            ],
        );
    }
}
