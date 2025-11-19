<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Query;

use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;

final readonly class GetBankAccountsByCustomerIdQueryHandler
{
    public function __construct(
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    /**
     * @return BankAccount[]
     */
    public function __invoke(GetBankAccountsByCustomerIdQuery $query): array
    {
        return $this->bankAccountRepository->findByCustomerId(
            new CustomerId($query->customerId),
        );
    }
}
