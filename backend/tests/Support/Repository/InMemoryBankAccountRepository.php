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
    private const string BANK_CODE = '10201026';

    /**
     * @var array<string, BankAccount>
     */
    private array $accounts = [];

    private int $accountNumberSequence = 0;

    public function save(BankAccount $bankAccount): void
    {
        $this->accounts[$bankAccount->id->getValue()] = $bankAccount;
    }

    public function findById(BankAccountId $id): ?BankAccount
    {
        return $this->accounts[$id->getValue()] ?? null;
    }

    public function findByIban(Iban $iban): ?BankAccount
    {
        foreach ($this->accounts as $account) {
            if ($account->iban->equals($iban)) {
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
                fn (BankAccount $account): bool => $account->customerId->equals($customerId),
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
                fn (BankAccount $account): bool => $account->isActive,
            ),
        );
    }

    public function existsByIban(Iban $iban): bool
    {
        return null !== $this->findByIban($iban);
    }

    public function nextIdentity(): BankAccountId
    {
        return BankAccountId::generate();
    }

    public function nextAccountNumber(): string
    {
        // Generate account number with bank code (8 digits) + sequence (18 digits)
        // This ensures consistency with production repository
        $randomPart = str_pad((string) $this->accountNumberSequence, 18, '0', STR_PAD_LEFT);
        $this->accountNumberSequence++;

        return self::BANK_CODE . $randomPart;
    }

    public function clear(): void
    {
        $this->accounts = [];
        $this->accountNumberSequence = 0;
    }
}
