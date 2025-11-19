<?php

declare(strict_types=1);

namespace App\Transaction\Application\Query;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;

final readonly class GetTransactionHistoryQueryHandler
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    /**
     * @return Transaction[]
     */
    public function __invoke(GetTransactionHistoryQuery $query): array
    {
        return $this->transactionRepository->findByBankAccountId(
            new BankAccountId($query->bankAccountId),
        );
    }
}
