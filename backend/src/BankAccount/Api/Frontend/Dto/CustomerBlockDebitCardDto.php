<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CustomerBlockDebitCardDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accountId,
    ) {
    }
}
