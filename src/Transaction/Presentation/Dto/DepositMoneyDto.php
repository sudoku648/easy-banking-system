<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class DepositMoneyDto
{
    #[Assert\NotBlank]
    public string $bankAccountId = '';

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $amount = 0.0;
}
