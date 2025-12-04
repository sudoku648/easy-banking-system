<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Provider\ClockInterface;
use App\Transaction\Domain\Entity\PendingInterbankTransfer;
use App\Transaction\Domain\Event\InterbankTransferProcessed;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\ValueObject\TransferId;

final readonly class ProcessInterbankTransfersCommandHandler
{
    public function __construct(
        private PendingInterbankTransferRepositoryInterface $pendingTransferRepository,
        private BankAccountRepositoryInterface $bankAccountRepository,
        private EventBus $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ProcessInterbankTransfersCommand $command): void
    {
        $pendingTransfers = $this->pendingTransferRepository->findPendingTransfers();

        foreach ($pendingTransfers as $transfer) {
            $this->processTransfer($transfer);
        }
    }

    private function processTransfer(PendingInterbankTransfer $transfer): void
    {
        $fromAccount = $this->bankAccountRepository->findById(
            BankAccountId::fromString(
                $transfer->fromBankAccountId->getValue(),
            ),
        );

        if (null === $fromAccount) {
            // Account was deleted - skip this transfer
            return;
        }

        // Withdraw the blocked money from the account
        $fromAccount->withdraw($transfer->amount);
        $fromAccount->unblockAmount($transfer->amount);

        $this->bankAccountRepository->save($fromAccount);

        // Mark transfer as processed
        $transactionId = TransferId::generate();
        $transfer->markAsProcessed($transactionId, $this->clock->now());
        $this->pendingTransferRepository->save($transfer);

        // Dispatch event
        $this->eventBus->dispatch(
            InterbankTransferProcessed::withData(
                $transfer->id,
                $fromAccount->iban,
                $transfer->toIban,
                $transfer->amount,
            ),
        );
    }
}
