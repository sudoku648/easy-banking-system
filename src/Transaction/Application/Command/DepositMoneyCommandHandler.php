<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\MoneyDeposited;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class DepositMoneyCommandHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private EventBus $eventBus,
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    public function __invoke(DepositMoneyCommand $command): void
    {
        $bankAccountId = new \App\BankAccount\Domain\ValueObject\BankAccountId($command->bankAccountId);

        $bankAccount = $this->bankAccountRepository->findById($bankAccountId);

        if ($bankAccount === null) {
            throw new \DomainException('Bank account not found');
        }

        $depositCurrency = Currency::fromString($command->currency);
        $depositAmount = new Money($command->amount, $depositCurrency);

        // For cash deposits, currency must match account currency
        if (!$depositCurrency->equals($bankAccount->getBalance()->getCurrency())) {
            throw new \DomainException('Deposit currency must match account currency');
        }

        // Deposit money to account
        $bankAccount->deposit($depositAmount);

        // Save account
        $this->bankAccountRepository->save($bankAccount);

        $occurredAt = new \DateTimeImmutable();

        // Create deposit transaction
        $depositTransaction = Transaction::createCashDeposit(
            $this->transactionRepository->nextIdentity(),
            new BankAccountId($command->bankAccountId),
            $depositAmount,
            $occurredAt,
        );

        $this->transactionRepository->save($depositTransaction);

        // Dispatch event
        $this->eventBus->dispatch(
            new MoneyDeposited(
                $depositTransaction->getId(),
                $bankAccount->getIban(),
                $depositAmount,
                $occurredAt,
            ),
        );
    }
}
