<?php

declare(strict_types=1);

namespace App\Transaction\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class EmploeeTransactionHistoryDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\Uuid]
        public ?string $customerId = null,
        #[Assert\Type(type: 'string')]
        #[Assert\Uuid]
        public ?string $bankAccountId = null,
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $page = 1,
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [10, 20, 50])]
        public int $limit = 10,
    ) {
    }
}
