<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Query;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;

final readonly class GetAllActiveBankAccountsQueryHandler
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    /**
     * @return array<array{id: string, iban: string, customerId: string, balance: int, currency: string}>
     */
    public function __invoke(GetAllActiveBankAccountsQuery $query): array
    {
        $accounts = $this->bankAccountRepository->findAllActive();

        return array_values(array_map(
            fn (BankAccount $account): array => [
                'id' => $account->getId()->getValue(),
                'iban' => $account->getIban()->getValue(),
                'customerId' => $account->getCustomerId()->getValue(),
                'balance' => $account->getBalance()->getAmount(),
                'currency' => $account->getBalance()->getCurrency()->value,
            ],
            $accounts,
        ));
    }
}
