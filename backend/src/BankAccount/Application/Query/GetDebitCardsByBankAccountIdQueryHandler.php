<?php

declare(strict_types=1);

namespace App\BankAccount\Application\Query;

use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;

final readonly class GetDebitCardsByBankAccountIdQueryHandler
{
    public function __construct(
        private DebitCardRepositoryInterface $debitCardRepository,
    ) {
    }

    /**
     * @return DebitCard[]
     */
    public function __invoke(GetDebitCardsByBankAccountIdQuery $query): array
    {
        return $this->debitCardRepository->findByBankAccountId(
            BankAccountId::fromString($query->bankAccountId),
        );
    }
}
