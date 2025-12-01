<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId as BankAccountIdVO;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Provider\ClockInterface;
use App\Shared\Domain\Provider\IbanProviderInterface;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\PendingInterbankTransfer;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\InterbankTransferInitiated;
use App\Transaction\Domain\Exception\InvalidTransferException;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class InterbankTransferCommandHandler
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
        private PendingInterbankTransferRepositoryInterface $pendingTransferRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private IbanProviderInterface $ibanProvider,
        private EventBus $eventBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(InterbankTransferCommand $command): void
    {
        $fromBankAccountId = BankAccountIdVO::fromString($command->fromBankAccountId);
        $toIban = Iban::fromString($command->toIban);

        $fromAccount = $this->bankAccountRepository->findById($fromBankAccountId);

        if (null === $fromAccount) {
            throw BankAccountNotFoundException::withId($command->fromBankAccountId);
        }

        $transferCurrency = Currency::fromString($command->currency);
        $transferAmount = new Money($command->amount, $transferCurrency);

        // Check if this is an internal or external transfer
        if ($this->ibanProvider->isInternalIban($toIban)) {
            // Internal transfer - use existing TransferMoneyCommand
            throw InvalidTransferException::useInternalTransferCommand();
        }

        // External/interbank transfer - block money and create pending transfer
        // Check if we have enough available balance
        if (!$fromAccount->getAvailableBalance()->isGreaterThanOrEqual($transferAmount)) {
            throw InvalidTransferException::insufficientBalance();
        }

        // Block the amount
        $fromAccount->blockAmount($transferAmount);
        $this->bankAccountRepository->save($fromAccount);

        $occurredAt = $this->clock->now();

        // Create a withdrawal transaction with INTERBANK_WITHDRAWAL type
        $transaction = Transaction::createInterbankWithdrawal(
            $this->transactionRepository->nextIdentity(),
            BankAccountId::fromString($command->fromBankAccountId),
            $transferAmount,
            $occurredAt,
        );

        $this->transactionRepository->save($transaction);

        // Create pending transfer record
        $pendingTransfer = PendingInterbankTransfer::create(
            $this->pendingTransferRepository->nextIdentity(),
            BankAccountId::fromString($command->fromBankAccountId),
            $toIban,
            $transferAmount,
            $occurredAt,
        );

        $this->pendingTransferRepository->save($pendingTransfer);

        // Dispatch event
        $this->eventBus->dispatch(
            InterbankTransferInitiated::withData(
                $pendingTransfer->id,
                $fromAccount->iban,
                $toIban,
                $transferAmount,
            ),
        );
    }
}
