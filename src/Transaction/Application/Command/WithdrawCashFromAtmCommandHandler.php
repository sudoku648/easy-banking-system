<?php

declare(strict_types=1);

namespace App\Transaction\Application\Command;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Event\CashWithdrawnFromAtm;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class WithdrawCashFromAtmCommandHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private EventBus $eventBus,
        private BankAccountRepositoryInterface $bankAccountRepository,
        private DebitCardRepositoryInterface $debitCardRepository,
        private ExchangeRateProviderInterface $exchangeRateProvider,
    ) {
    }

    public function __invoke(WithdrawCashFromAtmCommand $command): void
    {
        $cardNumber = DebitCardNumber::fromString($command->cardNumber);

        // Find debit card
        $debitCard = $this->debitCardRepository->findByCardNumber($cardNumber);

        if ($debitCard === null) {
            throw new \DomainException('Debit card not found');
        }

        // Check if card can be used for withdrawal
        if (!$debitCard->canBeUsedForWithdrawal()) {
            throw new \DomainException('Debit card is blocked or inactive');
        }

        // Get associated bank account
        $bankAccount = $this->bankAccountRepository->findById($debitCard->bankAccountId);

        if ($bankAccount === null) {
            throw new \DomainException('Bank account associated with card not found');
        }

        // Create ATM amount in the ATM's currency
        $atmCurrency = Currency::fromString($command->currency);
        $atmAmount = new Money($command->amount, $atmCurrency);

        // Convert to account's currency if different
        $accountCurrency = $bankAccount->balance->getCurrency();
        if (!$atmCurrency->equals($accountCurrency)) {
            $exchangeRate = $this->exchangeRateProvider->getRate($atmCurrency, $accountCurrency);
            $convertedAmount = (int) round($command->amount * $exchangeRate->getValue());
            $withdrawalAmount = new Money($convertedAmount, $accountCurrency);
        } else {
            $withdrawalAmount = $atmAmount;
        }

        // Withdraw money from account (will throw InsufficientFundsException if balance is insufficient)
        $bankAccount->withdraw($withdrawalAmount);

        // Save account
        $this->bankAccountRepository->save($bankAccount);

        $occurredAt = new \DateTimeImmutable();

        // Create ATM withdrawal transaction
        $withdrawalTransaction = Transaction::createAtmWithdrawal(
            $this->transactionRepository->nextIdentity(),
            BankAccountId::fromString($debitCard->bankAccountId->getValue()),
            $withdrawalAmount,
            $occurredAt,
        );

        $this->transactionRepository->save($withdrawalTransaction);

        // Dispatch event
        $this->eventBus->dispatch(
            new CashWithdrawnFromAtm(
                $withdrawalTransaction->id,
                $bankAccount->iban,
                $withdrawalAmount,
                $occurredAt,
            ),
        );
    }
}
