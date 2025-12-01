<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\Transaction\Domain\Entity\PendingInterbankTransfer;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\PendingTransferId;

final class InMemoryPendingInterbankTransferRepository implements PendingInterbankTransferRepositoryInterface
{
    /**
     * @var array<string, PendingInterbankTransfer>
     */
    private array $transfers = [];

    public function nextIdentity(): PendingTransferId
    {
        return PendingTransferId::generate();
    }

    public function save(PendingInterbankTransfer $transfer): void
    {
        $this->transfers[$transfer->id->getValue()] = $transfer;
    }

    public function findById(PendingTransferId $id): ?PendingInterbankTransfer
    {
        return $this->transfers[$id->getValue()] ?? null;
    }

    public function findPendingTransfers(): array
    {
        return array_values(
            array_filter(
                $this->transfers,
                fn (PendingInterbankTransfer $transfer): bool => !$transfer->isProcessed,
            ),
        );
    }

    public function findByBankAccountId(BankAccountId $bankAccountId): array
    {
        return array_values(
            array_filter(
                $this->transfers,
                fn (PendingInterbankTransfer $transfer): bool => $transfer->fromBankAccountId->equals($bankAccountId),
            ),
        );
    }

    public function clear(): void
    {
        $this->transfers = [];
    }
}
