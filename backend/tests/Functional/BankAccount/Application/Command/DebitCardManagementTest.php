<?php

declare(strict_types=1);

namespace App\Tests\Functional\BankAccount\Application\Command;

use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Command\BlockDebitCardCommandHandler;
use App\BankAccount\Application\Command\IssueDebitCardCommand;
use App\BankAccount\Application\Command\IssueDebitCardCommandHandler;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Event\DebitCardBlocked;
use App\BankAccount\Domain\Event\DebitCardIssued;
use App\BankAccount\Domain\Exception\DebitCardNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Shared\ApplicationTestCase;
use App\Tests\Support\Event\InMemoryEventBus;

final class DebitCardManagementTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private DebitCardRepositoryInterface $debitCardRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->debitCardRepository = self::getContainer()->get(DebitCardRepositoryInterface::class);
        $this->eventBus = self::getContainer()->get(EventBus::class);
    }

    private function getEventBus(): ?InMemoryEventBus
    {
        if ($this->eventBus instanceof InMemoryEventBus) {
            return $this->eventBus;
        }
        return null;
    }

    private function isUsingInMemoryEventBus(): bool
    {
        return $this->eventBus instanceof InMemoryEventBus;
    }

    public function testIssueDebitCardCreatesNewCard(): void
    {
        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );

        $customerId = CustomerId::generate();

        // Open a bank account first
        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        self::assertCount(1, $accounts);
        $account = $accounts[0];

        // Issue debit card
        $command = new IssueDebitCardCommand($account->id->getValue());
        $issueHandler($command);

        // Verify card was created
        $cards = $this->debitCardRepository->findByBankAccountId($account->id);
        self::assertCount(1, $cards);

        $card = $cards[0];
        self::assertTrue($card->bankAccountId->equals($account->id));
        self::assertTrue($card->isActive);
        self::assertNull($card->blockedAt);
        self::assertMatchesRegularExpression('/^\d{16}$/', $card->cardNumber->getValue());
    }

    public function testIssueDebitCardDispatchesDebitCardIssuedEvent(): void
    {
        if (!$this->isUsingInMemoryEventBus()) {
            self::markTestSkipped('Event assertions only work with InMemoryEventBus (functional mode)');
        }

        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );

        $customerId = CustomerId::generate();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'EUR'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        $eventBus = $this->getEventBus();
        $eventBus->clear();

        $command = new IssueDebitCardCommand($account->id->getValue());
        $issueHandler($command);

        $events = $eventBus->getDispatchedEventsOfType(DebitCardIssued::class);
        self::assertCount(1, $events);

        $event = $events[0];
        self::assertTrue($event->bankAccountId->equals($account->id));
    }

    public function testBlockDebitCardSuccessfully(): void
    {
        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );
        $blockHandler = new BlockDebitCardCommandHandler($this->debitCardRepository, $this->eventBus);

        $customerId = CustomerId::generate();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue card
        $issueHandler(new IssueDebitCardCommand($account->id->getValue()));

        $cards = $this->debitCardRepository->findByBankAccountId($account->id);
        $card = $cards[0];
        self::assertTrue($card->isActive);

        // Block card
        $blockCommand = new BlockDebitCardCommand($card->id->getValue());
        $blockHandler($blockCommand);

        // Verify card is blocked
        $blockedCard = $this->debitCardRepository->findById($card->id);
        self::assertNotNull($blockedCard);
        self::assertFalse($blockedCard->isActive);
        self::assertNotNull($blockedCard->blockedAt);
    }

    public function testBlockDebitCardDispatchesDebitCardBlockedEvent(): void
    {
        if (!$this->isUsingInMemoryEventBus()) {
            self::markTestSkipped('Event assertions only work with InMemoryEventBus (functional mode)');
        }

        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );
        $blockHandler = new BlockDebitCardCommandHandler($this->debitCardRepository, $this->eventBus);

        $customerId = CustomerId::generate();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        $issueHandler(new IssueDebitCardCommand($account->id->getValue()));

        $cards = $this->debitCardRepository->findByBankAccountId($account->id);
        $card = $cards[0];

        $eventBus = $this->getEventBus();
        $eventBus->clear();

        $blockCommand = new BlockDebitCardCommand($card->id->getValue());
        $blockHandler($blockCommand);

        $events = $eventBus->getDispatchedEventsOfType(DebitCardBlocked::class);
        self::assertCount(1, $events);

        $event = $events[0];
        self::assertTrue($event->debitCardId->equals($card->id));
    }

    public function testIssueDebitCardThrowsExceptionForNonExistentAccount(): void
    {
        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );

        $nonExistentId = CustomerId::generate()->getValue();
        $command = new IssueDebitCardCommand($nonExistentId);

        $this->expectException(\Exception::class);

        $issueHandler($command);
    }

    public function testBlockDebitCardThrowsExceptionForNonExistentCard(): void
    {
        $blockHandler = new BlockDebitCardCommandHandler($this->debitCardRepository, $this->eventBus);

        $nonExistentId = DebitCardId::generate()->getValue();
        $command = new BlockDebitCardCommand($nonExistentId);

        $this->expectException(DebitCardNotFoundException::class);

        $blockHandler($command);
    }

    public function testMultipleDebitCardsCanBeIssuedForSameAccount(): void
    {
        $issueHandler = new IssueDebitCardCommandHandler(
            $this->debitCardRepository,
            $this->bankAccountRepository,
            $this->eventBus,
        );

        $customerId = CustomerId::generate();

        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $openHandler(new OpenBankAccountCommand($customerId->getValue(), 'PLN'));

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue multiple cards
        $issueHandler(new IssueDebitCardCommand($account->id->getValue()));
        $issueHandler(new IssueDebitCardCommand($account->id->getValue()));
        $issueHandler(new IssueDebitCardCommand($account->id->getValue()));

        $cards = $this->debitCardRepository->findByBankAccountId($account->id);
        self::assertCount(3, $cards);

        // Verify all cards have unique card numbers
        $cardNumbers = array_map(fn ($card) => $card->cardNumber->getValue(), $cards);
        self::assertCount(3, array_unique($cardNumbers));
    }
}
