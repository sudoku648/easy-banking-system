<?php

declare(strict_types=1);

namespace App\Transaction\Api\External\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class WithdrawCashFromAtmDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{16}$/')]
        public string $cardNumber,
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $amount,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP'])]
        public string $currency,
    ) {
    }
}
