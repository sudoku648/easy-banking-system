<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OpenAccountExistingCustomerDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $customerId,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP'])]
        public string $currency,
    ) {
    }
}
