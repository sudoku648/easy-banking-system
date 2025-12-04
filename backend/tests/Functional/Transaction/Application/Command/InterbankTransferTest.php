<?php

declare(strict_types=1);

namespace App\Tests\Functional\Transaction\Application\Command;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Shared\ApplicationTestCase;
use App\Transaction\Application\Command\InterbankTransferCommand;
use App\Transaction\Application\Command\InterbankTransferCommandHandler;
use App\Transaction\Domain\Exception\InvalidTransferException;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\ValueObject\TransactionType;

final class InterbankTransferTest extends ApplicationTestCase
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private PendingInterbankTransferRepositoryInterface $pendingTransferRepository;
    private InterbankTransferCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bankAccountRepository = self::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->transactionRepository = self::getContainer()->get(TransactionRepositoryInterface::class);
        $this->pendingTransferRepository = self::getContainer()->get(PendingInterbankTransferRepositoryInterface::class);
        $this->handler = self::getContainer()->get(InterbankTransferCommandHandler::class);
    }

    public function testInterbankTransferBlocksMoneyAndCreatesWithdrawalTransaction(): void
    {
        // Arrange
        $fromAccount = BankAccount::open(
            $this->bankAccountRepository->nextIdentity(),
            Iban::fromString('PL10102010260000000000000001'),
            CustomerId::generate(),
            new Money(50000, Currency::PLN), // 500.00 PLN
        );
        $this->bankAccountRepository->save($fromAccount);

        $externalIban = 'GB33BUKB20201555555555'; // External bank IBAN

        // Act
        $command = new InterbankTransferCommand(
            $fromAccount->id->getValue(),
            $externalIban,
            10000, // 100.00 PLN
            Currency::PLN->value,
        );

        ($this->handler)($command);

        // Assert
        $updatedAccount = $this->bankAccountRepository->findById($fromAccount->id);
        self::assertNotNull($updatedAccount);
        self::assertEquals(50000, $updatedAccount->balance->getAmount(), 'Balance should remain unchanged');
        self::assertEquals(10000, $updatedAccount->blockedAmount->getAmount(), 'Amount should be blocked');
        self::assertEquals(40000, $updatedAccount->getAvailableBalance()->getAmount(), 'Available balance should be reduced');

        // Check that pending transfer was created
        $pendingTransfers = $this->pendingTransferRepository->findByBankAccountId(
            BankAccountId::fromString($fromAccount->id->getValue()),
        );
        self::assertCount(1, $pendingTransfers);
        self::assertEquals($externalIban, $pendingTransfers[0]->toIban->getValue());
        self::assertEquals(10000, $pendingTransfers[0]->amount->getAmount());
        self::assertFalse($pendingTransfers[0]->isProcessed);

        // Check that a transaction was created (but not processed yet)
        $transactions = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($fromAccount->id->getValue()),
        );
        self::assertCount(1, $transactions);
        self::assertEquals(TransactionType::TRANSFER_WITHDRAWAL, $transactions[0]->type);
    }

    public function testInterbankTransferFailsWithInternalIban(): void
    {
        // Arrange
        $fromAccount = BankAccount::open(
            $this->bankAccountRepository->nextIdentity(),
            Iban::fromString('PL10102010260000000000000001'),
            CustomerId::generate(),
            new Money(50000, Currency::PLN),
        );
        $this->bankAccountRepository->save($fromAccount);

        $internalIban = 'PL10102010260000000000000002'; // Internal bank IBAN (same bank code)

        // Act & Assert
        $this->expectException(InvalidTransferException::class);

        $command = new InterbankTransferCommand(
            $fromAccount->id->getValue(),
            $internalIban,
            10000,
            Currency::PLN->value,
        );

        ($this->handler)($command);
    }

    public function testInterbankTransferFailsWithInsufficientFunds(): void
    {
        // Arrange
        $fromAccount = BankAccount::open(
            $this->bankAccountRepository->nextIdentity(),
            Iban::fromString('PL10102010260000000000000001'),
            CustomerId::generate(),
            new Money(5000, Currency::PLN), // Only 50.00 PLN
        );
        $this->bankAccountRepository->save($fromAccount);

        $externalIban = 'GB33BUKB20201555555555';

        // Act & Assert
        $this->expectException(InvalidTransferException::class);

        $command = new InterbankTransferCommand(
            $fromAccount->id->getValue(),
            $externalIban,
            10000, // Try to transfer 100.00 PLN
            Currency::PLN->value,
        );

        ($this->handler)($command);
    }
}
