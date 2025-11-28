<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

use App\BankAccount\Domain\Event\DebitCardBlocked;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\Shared\Domain\Event\EventBus;

final readonly class BlockDebitCardCommandHandler
{
    public function __construct(
        private DebitCardRepositoryInterface $debitCardRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(BlockDebitCardCommand $command): void
    {
        $debitCardId = DebitCardId::fromString($command->debitCardId);

        $debitCard = $this->debitCardRepository->findById($debitCardId);
        if ($debitCard === null) {
            throw new \DomainException('Debit card not found');
        }

        $debitCard->block();

        $this->debitCardRepository->save($debitCard);

        $this->eventBus->dispatch(
            new DebitCardBlocked(
                $debitCard->id,
                new \DateTimeImmutable(),
            ),
        );
    }
}
