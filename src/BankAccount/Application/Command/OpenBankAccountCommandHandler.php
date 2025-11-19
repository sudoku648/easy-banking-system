<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Command;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Event\BankAccountOpened;
use App\BankAccount\Domain\Exception\IbanAlreadyExistsException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;

final readonly class OpenBankAccountCommandHandler
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(OpenBankAccountCommand $command): void
    {
        $currency = Currency::fromString($command->currency);
        $accountNumber = $this->bankAccountRepository->nextAccountNumber();
        $iban = Iban::generatePolishIban($accountNumber);

        if ($this->bankAccountRepository->existsByIban($iban)) {
            throw IbanAlreadyExistsException::forIban($iban->getValue());
        }

        $bankAccount = BankAccount::open(
            $this->bankAccountRepository->nextIdentity(),
            $iban,
            new CustomerId($command->customerId),
            Money::zero($currency),
        );

        $this->bankAccountRepository->save($bankAccount);

        $this->eventBus->dispatch(
            new BankAccountOpened(
                $bankAccount->getId(),
                $bankAccount->getIban(),
                $bankAccount->getCustomerId(),
                $currency,
                new \DateTimeImmutable(),
            ),
        );
    }
}
