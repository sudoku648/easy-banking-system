<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Iban;

final class InMemoryBankAccountRepository implements BankAccountRepositoryInterface
{
    /**
     * @var array<string, BankAccount>
     */
    private array $accounts = [];

    private int $accountNumberSequence = 0;

    public function save(BankAccount $bankAccount): void
    {
        $this->accounts[$bankAccount->getId()->getValue()] = $bankAccount;
    }

    public function findById(BankAccountId $id): ?BankAccount
    {
        return $this->accounts[$id->getValue()] ?? null;
    }

    public function findByIban(Iban $iban): ?BankAccount
    {
        foreach ($this->accounts as $account) {
            if ($account->getIban()->equals($iban)) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @return BankAccount[]
     */
    public function findByCustomerId(CustomerId $customerId): array
    {
        return array_values(
            array_filter(
                $this->accounts,
                fn (BankAccount $account): bool => $account->getCustomerId()->equals($customerId),
            ),
        );
    }

    /**
     * @return BankAccount[]
     */
    public function findAllActive(): array
    {
        return array_values(
            array_filter(
                $this->accounts,
                fn (BankAccount $account): bool => $account->isActive(),
            ),
        );
    }

    public function existsByIban(Iban $iban): bool
    {
        return $this->findByIban($iban) !== null;
    }

    public function nextIdentity(): BankAccountId
    {
        return BankAccountId::generate();
    }

    public function nextAccountNumber(): string
    {
        $accountNumber = str_pad((string) $this->accountNumberSequence, 26, '0', STR_PAD_LEFT);
        $this->accountNumberSequence++;

        return $accountNumber;
    }

    public function clear(): void
    {
        $this->accounts = [];
        $this->accountNumberSequence = 0;
    }
}
