<?php

declare(strict_types=1);

namespace App\Transaction\Application\EventHandler;

use App\BankAccount\Domain\Event\BankAccountClosed;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class BankAccountClosedEventHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(BankAccountClosed $event): void
    {
        if ($event->withdrawnBalance->isZero()) {
            return;
        }

        $transaction = Transaction::createCashWithdrawal(
            $this->transactionRepository->nextIdentity(),
            new BankAccountId($event->bankAccountId->getValue()),
            $event->withdrawnBalance,
            $event->occurredOn(),
        );

        $this->transactionRepository->save($transaction);
    }
}
