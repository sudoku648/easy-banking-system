<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Persistence\Repository;

use App\Transaction\Domain\Entity\PendingInterbankTransfer;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\PendingTransferId;

interface PendingInterbankTransferRepositoryInterface
{
    public function nextIdentity(): PendingTransferId;

    public function save(PendingInterbankTransfer $transfer): void;

    public function findById(PendingTransferId $id): ?PendingInterbankTransfer;

    /**
     * @return PendingInterbankTransfer[]
     */
    public function findPendingTransfers(): array;

    /**
     * @return PendingInterbankTransfer[]
     */
    public function findByBankAccountId(BankAccountId $bankAccountId): array;
}
