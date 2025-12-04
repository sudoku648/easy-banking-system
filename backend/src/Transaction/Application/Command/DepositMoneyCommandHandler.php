<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId as BankAccountIdVO;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Provider\ClockInterface;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\MoneyDeposited;
use App\Transaction\Domain\Exception\CurrencyMismatchException;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class DepositMoneyCommandHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private EventBus $eventBus,
        private BankAccountRepositoryInterface $bankAccountRepository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(DepositMoneyCommand $command): void
    {
        $bankAccountId = BankAccountIdVO::fromString($command->bankAccountId);

        $bankAccount = $this->bankAccountRepository->findById($bankAccountId);

        if (null === $bankAccount) {
            throw BankAccountNotFoundException::withId($command->bankAccountId);
        }

        $depositCurrency = Currency::fromString($command->currency);
        $depositAmount = new Money($command->amount, $depositCurrency);

        // For cash deposits, currency must match account currency
        if (!$depositCurrency->equals($bankAccount->balance->getCurrency())) {
            throw CurrencyMismatchException::forDeposit();
        }

        // Deposit money to account
        $bankAccount->deposit($depositAmount);

        // Save account
        $this->bankAccountRepository->save($bankAccount);

        $occurredAt = $this->clock->now();

        // Create deposit transaction
        $depositTransaction = Transaction::createCashDeposit(
            $this->transactionRepository->nextIdentity(),
            BankAccountId::fromString($command->bankAccountId),
            $depositAmount,
            $occurredAt,
        );

        $this->transactionRepository->save($depositTransaction);

        // Dispatch event
        $this->eventBus->dispatch(
            MoneyDeposited::withData(
                $depositTransaction->id,
                $bankAccount->iban,
                $depositAmount,
            ),
        );
    }
}
