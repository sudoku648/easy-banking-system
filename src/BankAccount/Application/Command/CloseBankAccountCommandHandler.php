<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

use App\BankAccount\Domain\Event\BankAccountClosed;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Event\EventBus;

final readonly class CloseBankAccountCommandHandler
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CloseBankAccountCommand $command): void
    {
        $bankAccountId = new BankAccountId($command->bankAccountId);
        $bankAccount = $this->bankAccountRepository->findById($bankAccountId);

        if ($bankAccount === null) {
            throw BankAccountNotFoundException::withId($bankAccountId->getValue());
        }

        $balanceToWithdraw = $bankAccount->getBalance();

        // If there's balance, withdraw it as cash
        if ($balanceToWithdraw->isPositive()) {
            $bankAccount->withdraw($balanceToWithdraw);
        }

        $bankAccount->close();
        $this->bankAccountRepository->save($bankAccount);

        $this->eventBus->dispatch(
            new BankAccountClosed(
                $bankAccount->getId(),
                $balanceToWithdraw,
                new \DateTimeImmutable(),
            ),
        );
    }
}
