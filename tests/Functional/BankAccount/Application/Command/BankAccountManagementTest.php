<?php

declare(strict_types=1);

namespace App\Tests\Functional\BankAccount\Application\Command;

use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Command\CloseBankAccountCommandHandler;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommandHandler;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Event\BankAccountClosed;
use App\BankAccount\Domain\Event\BankAccountOpened;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Tests\Shared\ApplicationTestCase;
use App\Tests\Support\Event\InMemoryEventBus;

final class BankAccountManagementTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
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

    public function testOpenBankAccountCreatesNewAccount(): void
    {
        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());

        $command = new OpenBankAccountCommand(
            customerId: $customerId->getValue(),
            currency: 'PLN',
        );

        $handler($command);

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);

        self::assertCount(1, $accounts);
        $account = $accounts[0];
        self::assertTrue($account->getCustomerId()->equals($customerId));
        self::assertSame(Currency::PLN, $account->getBalance()->getCurrency());
        self::assertTrue($account->getBalance()->isZero());
        self::assertTrue($account->isActive());
    }

    public function testOpenBankAccountDispatchesBankAccountOpenedEvent(): void
    {
        if (!$this->isUsingInMemoryEventBus()) {
            self::markTestSkipped('Event assertions only work with InMemoryEventBus (functional mode)');
        }

        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());

        $command = new OpenBankAccountCommand(
            customerId: $customerId->getValue(),
            currency: 'EUR',
        );

        $handler($command);

        $eventBus = $this->getEventBus();
        $events = $eventBus->getDispatchedEventsOfType(BankAccountOpened::class);

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertSame(Currency::EUR, $event->currency);
        self::assertTrue($event->customerId->equals($customerId));
    }

    public function testOpenMultipleBankAccountsForSameCustomer(): void
    {
        $handler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());

        $command1 = new OpenBankAccountCommand($customerId->getValue(), 'PLN');
        $command2 = new OpenBankAccountCommand($customerId->getValue(), 'EUR');

        $handler($command1);
        $handler($command2);

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);

        self::assertCount(2, $accounts);
        
        // Extract currencies from accounts
        $currencies = array_map(
            fn (BankAccount $account): Currency => $account->getBalance()->getCurrency(),
            $accounts,
        );
        
        // Verify both currencies are present (order may vary)
        self::assertContains(Currency::PLN, $currencies);
        self::assertContains(Currency::EUR, $currencies);
    }

    public function testCloseBankAccountWithZeroBalance(): void
    {
        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);

        $customerId = CustomerId::generate();
        $this->ensureCustomerExists($customerId->getValue());
        $openCommand = new OpenBankAccountCommand($customerId->getValue(), 'PLN');
        $openHandler($openCommand);

        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $accountId = $accounts[0]->getId();

        if ($this->isUsingInMemoryEventBus()) {
            $this->getEventBus()->clear();
        }
        
        $closeCommand = new CloseBankAccountCommand($accountId->getValue());
        $closeHandler($closeCommand);

        $account = $this->bankAccountRepository->findById($accountId);

        self::assertNotNull($account);
        self::assertFalse($account->isActive());
        self::assertTrue($account->getBalance()->isZero());

        if ($this->isUsingInMemoryEventBus()) {
            $eventBus = $this->getEventBus();
            $events = $eventBus->getDispatchedEventsOfType(BankAccountClosed::class);
            self::assertCount(1, $events);
        }
    }

    public function testCloseBankAccountThrowsExceptionForNonExistentAccount(): void
    {
        $handler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $nonExistentId = CustomerId::generate()->getValue();

        $command = new CloseBankAccountCommand($nonExistentId);

        $this->expectException(BankAccountNotFoundException::class);

        $handler($command);
    }

    public function testFindAllActiveReturnsOnlyActiveAccounts(): void
    {
        $openHandler = new OpenBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);
        $closeHandler = new CloseBankAccountCommandHandler($this->bankAccountRepository, $this->eventBus);

        $customerId1 = CustomerId::generate();
        $customerId2 = CustomerId::generate();

        $this->ensureCustomerExists($customerId1->getValue());
        $this->ensureCustomerExists($customerId2->getValue());

        $openHandler(new OpenBankAccountCommand($customerId1->getValue(), 'PLN'));
        $openHandler(new OpenBankAccountCommand($customerId2->getValue(), 'EUR'));

        $activeAccounts = $this->bankAccountRepository->findAllActive();
        self::assertCount(2, $activeAccounts);

        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $closeHandler(new CloseBankAccountCommand($accounts1[0]->getId()->getValue()));

        $activeAccounts = $this->bankAccountRepository->findAllActive();
        self::assertCount(1, $activeAccounts);
        self::assertTrue($activeAccounts[0]->getCustomerId()->equals($customerId2));
    }
}
