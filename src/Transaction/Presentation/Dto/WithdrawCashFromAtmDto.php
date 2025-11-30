<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class WithdrawCashFromAtmDto
{
    #[Assert\NotBlank]
    public ?string $cardNumber = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $amount = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP'])]
    public ?string $currency = null;
}
