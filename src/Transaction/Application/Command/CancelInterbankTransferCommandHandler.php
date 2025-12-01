<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransactionId;

final readonly class CancelInterbankTransferCommandHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private PendingInterbankTransferRepositoryInterface $pendingTransferRepository,
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    public function __invoke(CancelInterbankTransferCommand $command): void
    {
        $transactionId = TransactionId::fromString($command->transactionId);

        $transaction = $this->transactionRepository->findById($transactionId);

        if ($transaction === null) {
            throw new \DomainException('Transaction not found');
        }

        // Cancel the transaction (will throw exception if not in ORDERED status)
        $transaction->cancel();

        // Unblock the amount
        $bankAccountId = BankAccountId::fromString($transaction->bankAccountId->getValue());
        $bankAccount = $this->bankAccountRepository->findById($bankAccountId);

        if ($bankAccount === null) {
            throw new \DomainException('Bank account not found');
        }

        $bankAccount->unblockAmount($transaction->amount);

        // Save changes
        $this->transactionRepository->save($transaction);
        $this->bankAccountRepository->save($bankAccount);
    }
}
