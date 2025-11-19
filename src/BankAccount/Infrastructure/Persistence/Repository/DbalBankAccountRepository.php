<?php

declare(strict_types=1);

namespace App\BankAccount\Infrastructure\Persistence\Repository;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\DBAL\Connection;

final readonly class DbalBankAccountRepository implements BankAccountRepositoryInterface
{
    private const string BANK_CODE = '10201026';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(BankAccount $bankAccount): void
    {
        $data = [
            'id' => $bankAccount->getId()->getValue(),
            'iban' => $bankAccount->getIban()->getValue(),
            'customer_id' => $bankAccount->getCustomerId()->getValue(),
            'balance' => $bankAccount->getBalance()->getAmount(),
            'currency' => $bankAccount->getBalance()->getCurrency()->value,
            'is_active' => $bankAccount->isActive(),
        ];

        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM bank_account WHERE id = :id',
            ['id' => $bankAccount->getId()->getValue()],
        );

        if ($exists) {
            $this->connection->update('bank_account', $data, ['id' => $bankAccount->getId()->getValue()]);
        } else {
            $this->connection->insert('bank_account', $data);
        }
    }

    public function findById(BankAccountId $id): ?BankAccount
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM bank_account WHERE id = :id',
            ['id' => $id->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByIban(Iban $iban): ?BankAccount
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM bank_account WHERE iban = :iban',
            ['iban' => $iban->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByCustomerId(CustomerId $customerId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM bank_account WHERE customer_id = :customer_id ORDER BY iban',
            ['customer_id' => $customerId->getValue()],
        );

        return array_map(fn (array $data): BankAccount => $this->mapToEntity($data), $rows);
    }

    public function existsByIban(Iban $iban): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM bank_account WHERE iban = :iban',
            ['iban' => $iban->getValue()],
        );

        return $count > 0;
    }

    public function nextIdentity(): BankAccountId
    {
        return BankAccountId::generate();
    }

    public function nextAccountNumber(): string
    {
        // Generate a unique 26-digit account number
        // Format: BANK_CODE (8 digits) + RANDOM (18 digits)
        $randomPart = str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT);

        return self::BANK_CODE . $randomPart;
    }

    /**
     * @param array{
     *   id: string,
     *   iban: string,
     *   customer_id: string,
     *   balance: int,
     *   currency: string,
     *   is_active: bool,
     * } $data
     */
    private function mapToEntity(array $data): BankAccount
    {
        return new BankAccount(
            new BankAccountId($data['id']),
            new Iban($data['iban']),
            new CustomerId($data['customer_id']),
            new Money((int) $data['balance'], Currency::fromString($data['currency'])),
            (bool) $data['is_active'],
        );
    }
}
