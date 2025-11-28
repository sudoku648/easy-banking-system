<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Event\DebitCardIssued;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Event\EventBus;

final readonly class IssueDebitCardCommandHandler
{
    public function __construct(
        private DebitCardRepositoryInterface $debitCardRepository,
        private BankAccountRepositoryInterface $bankAccountRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(IssueDebitCardCommand $command): void
    {
        $bankAccountId = BankAccountId::fromString($command->bankAccountId);

        // Verify bank account exists
        $bankAccount = $this->bankAccountRepository->findById($bankAccountId);
        if ($bankAccount === null) {
            throw BankAccountNotFoundException::withId($bankAccountId->getValue());
        }

        // Generate unique card number
        $cardNumber = $this->debitCardRepository->generateCardNumber();

        // Issue new debit card
        $debitCard = DebitCard::issue(
            $this->debitCardRepository->nextIdentity(),
            $cardNumber,
            $bankAccountId,
        );

        $this->debitCardRepository->save($debitCard);

        $this->eventBus->dispatch(
            DebitCardIssued::withData(
                $debitCard->id,
                $debitCard->cardNumber,
                $debitCard->bankAccountId,
            ),
        );
    }
}
