<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Entity;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\PendingTransferId;
use App\Transaction\Domain\ValueObject\TransferId;

final class PendingInterbankTransfer
{
    private function __construct(
        public readonly PendingTransferId $id,
        public readonly BankAccountId $fromBankAccountId,
        public readonly Iban $toIban,
        public readonly Money $amount,
        public readonly \DateTimeImmutable $createdAt,
        public private(set) bool $isProcessed = false,
        public private(set) ?\DateTimeImmutable $processedAt = null,
        public private(set) ?TransferId $transactionId = null,
    ) {
    }

    /**
     * @param array{
     *   id: string,
     *   from_bank_account_id: string,
     *   to_iban: string,
     *   amount: int,
     *   currency: string,
     *   created_at: string,
     *   is_processed: bool,
     *   processed_at: string|null,
     *   transaction_id: string|null,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            PendingTransferId::fromString($data['id']),
            BankAccountId::fromString($data['from_bank_account_id']),
            Iban::fromString($data['to_iban']),
            new Money($data['amount'], Currency::from($data['currency'])),
            new \DateTimeImmutable($data['created_at']),
            (bool) $data['is_processed'],
            null !== $data['processed_at'] ? new \DateTimeImmutable($data['processed_at']) : null,
            null !== $data['transaction_id'] ? TransferId::fromString($data['transaction_id']) : null,
        );
    }

    public static function create(
        PendingTransferId $id,
        BankAccountId $fromBankAccountId,
        Iban $toIban,
        Money $amount,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            $fromBankAccountId,
            $toIban,
            $amount,
            $createdAt,
        );
    }

    public function markAsProcessed(TransferId $transactionId, \DateTimeImmutable $processedAt): void
    {
        if ($this->isProcessed) {
            throw new \DomainException('Transfer already processed');
        }

        $this->isProcessed = true;
        $this->processedAt = $processedAt;
        $this->transactionId = $transactionId;
    }
}
