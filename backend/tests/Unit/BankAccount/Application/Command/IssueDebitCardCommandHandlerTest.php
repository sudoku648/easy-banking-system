<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Application\Command;

use App\BankAccount\Application\Command\IssueDebitCardCommand;
use App\BankAccount\Application\Command\IssueDebitCardCommandHandler;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Event\DebitCardIssued;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class IssueDebitCardCommandHandlerTest extends TestCase
{
    private DebitCardRepositoryInterface&MockObject $debitCardRepository;
    private BankAccountRepositoryInterface&MockObject $bankAccountRepository;
    private EventBus&MockObject $eventBus;
    private IssueDebitCardCommandHandler $handler;

    protected function setUp(): void
    {
        $this->debitCardRepository = $this->createMock(DebitCardRepositoryInterface::class);
        $this->bankAccountRepository = $this->createMock(BankAccountRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );
    }

    public function testIssueDebitCardSuccessfully(): void
    {
        $bankAccountId = BankAccountId::generate();
        $command = new IssueDebitCardCommand($bankAccountId->getValue());

        $bankAccount = BankAccount::open(
            $bankAccountId,
            Iban::generatePolishIban('12345678901234567890123456'),
            CustomerId::generate(),
            Money::zero(Currency::PLN),
        );

        $cardNumber = DebitCardNumber::fromString('4532123456789012');
        $debitCardId = DebitCardId::generate();

        $this->bankAccountRepository
            ->expects(self::once())
            ->method('findById')
            ->with(self::callback(fn (BankAccountId $id): bool => $id->equals($bankAccountId)))
            ->willReturn($bankAccount);

        $this->debitCardRepository
            ->expects(self::once())
            ->method('generateCardNumber')
            ->willReturn($cardNumber);

        $this->debitCardRepository
            ->expects(self::once())
            ->method('nextIdentity')
            ->willReturn($debitCardId);

        $this->debitCardRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (DebitCard $card) use ($debitCardId, $cardNumber, $bankAccountId): bool {
                return $card->id->equals($debitCardId)
                    && $card->cardNumber->equals($cardNumber)
                    && $card->bankAccountId->equals($bankAccountId)
                    && $card->isActive;
            }));

        $this->eventBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DebitCardIssued::class));

        ($this->handler)($command);
    }

    public function testIssueDebitCardThrowsExceptionForNonExistentAccount(): void
    {
        $bankAccountId = BankAccountId::generate();
        $command = new IssueDebitCardCommand($bankAccountId->getValue());

        $this->bankAccountRepository
            ->expects(self::once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(BankAccountNotFoundException::class);

        ($this->handler)($command);
    }
}
