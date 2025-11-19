<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\MoneyTransferred;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class TransferMoneyCommandHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private ExchangeRateProviderInterface $exchangeRateProvider,
        private EventBus $eventBus,
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    public function __invoke(TransferMoneyCommand $command): void
    {
        $fromBankAccountId = new \App\BankAccount\Domain\ValueObject\BankAccountId($command->fromBankAccountId);
        $toBankAccountId = new \App\BankAccount\Domain\ValueObject\BankAccountId($command->toBankAccountId);

        $fromAccount = $this->bankAccountRepository->findById($fromBankAccountId);
        $toAccount = $this->bankAccountRepository->findById($toBankAccountId);

        if ($fromAccount === null) {
            throw new \DomainException('Source bank account not found');
        }

        if ($toAccount === null) {
            throw new \DomainException('Target bank account not found');
        }

        $transferCurrency = Currency::fromString($command->currency);
        $transferAmount = new Money($command->amount, $transferCurrency);

        // Get exchange rates if needed
        $withdrawalRate = $this->exchangeRateProvider->getRate(
            $transferCurrency,
            $fromAccount->getBalance()->getCurrency(),
        );

        $depositRate = $this->exchangeRateProvider->getRate(
            $transferCurrency,
            $toAccount->getBalance()->getCurrency(),
        );

        // Convert to account currencies
        $amountToWithdraw = $withdrawalRate->convert($transferAmount);
        $amountToDeposit = $depositRate->convert($transferAmount);

        // Execute transfer
        $fromAccount->withdraw($amountToWithdraw);
        $toAccount->deposit($amountToDeposit);

        // Save accounts
        $this->bankAccountRepository->save($fromAccount);
        $this->bankAccountRepository->save($toAccount);

        $occurredAt = new \DateTimeImmutable();

        // Create withdrawal transaction
        $withdrawalTransaction = Transaction::createTransferWithdrawal(
            $this->transactionRepository->nextIdentity(),
            new BankAccountId($command->fromBankAccountId),
            $amountToWithdraw,
            $transferAmount,
            $withdrawalRate,
            $occurredAt,
        );

        // Create deposit transaction
        $depositTransaction = Transaction::createTransferDeposit(
            $this->transactionRepository->nextIdentity(),
            new BankAccountId($command->toBankAccountId),
            $amountToDeposit,
            $transferAmount,
            $depositRate,
            $occurredAt,
        );

        $this->transactionRepository->save($withdrawalTransaction);
        $this->transactionRepository->save($depositTransaction);

        // Dispatch event
        $this->eventBus->dispatch(
            new MoneyTransferred(
                $withdrawalTransaction->getId(),
                $fromAccount->getIban(),
                $toAccount->getIban(),
                $transferAmount,
                $occurredAt,
            ),
        );
    }
}
