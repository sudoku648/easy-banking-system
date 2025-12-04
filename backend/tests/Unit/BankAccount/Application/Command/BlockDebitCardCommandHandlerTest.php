<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Application\Command;

use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Command\BlockDebitCardCommandHandler;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Event\DebitCardBlocked;
use App\BankAccount\Domain\Exception\DebitCardNotFoundException;
use App\BankAccount\Domain\Exception\DebitCardStateException;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use App\Shared\Domain\Event\EventBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BlockDebitCardCommandHandlerTest extends TestCase
{
    private DebitCardRepositoryInterface&MockObject $debitCardRepository;
    private EventBus&MockObject $eventBus;
    private BlockDebitCardCommandHandler $handler;

    protected function setUp(): void
    {
        $this->debitCardRepository = $this->createMock(DebitCardRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new BlockDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->eventBus,
        );
    }

    public function testBlockDebitCardSuccessfully(): void
    {
        $debitCardId = DebitCardId::generate();
        $command = new BlockDebitCardCommand($debitCardId->getValue());

        $debitCard = DebitCard::issue(
            $debitCardId,
            DebitCardNumber::fromString('4532123456789012'),
            BankAccountId::generate(),
        );

        $this->debitCardRepository
            ->expects(self::once())
            ->method('findById')
            ->with(self::callback(fn (DebitCardId $id): bool => $id->equals($debitCardId)))
            ->willReturn($debitCard);

        $this->debitCardRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(fn (DebitCard $card): bool => !$card->isActive && null !== $card->blockedAt));

        $this->eventBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DebitCardBlocked::class));

        ($this->handler)($command);
    }

    public function testBlockDebitCardThrowsExceptionForNonExistentCard(): void
    {
        $debitCardId = DebitCardId::generate();
        $command = new BlockDebitCardCommand($debitCardId->getValue());

        $this->debitCardRepository
            ->expects(self::once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(DebitCardNotFoundException::class);

        ($this->handler)($command);
    }

    public function testBlockDebitCardThrowsExceptionWhenAlreadyBlocked(): void
    {
        $debitCardId = DebitCardId::generate();
        $command = new BlockDebitCardCommand($debitCardId->getValue());

        $debitCard = DebitCard::issue(
            $debitCardId,
            DebitCardNumber::fromString('4532123456789012'),
            BankAccountId::generate(),
        );

        // Block the card first
        $debitCard->block();

        $this->debitCardRepository
            ->expects(self::once())
            ->method('findById')
            ->willReturn($debitCard);

        $this->expectException(DebitCardStateException::class);

        ($this->handler)($command);
    }
}
