<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class TransferMoneyDto
{
    #[Assert\NotBlank]
    public ?string $fromBankAccountId = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 26, max: 34)]
    public ?string $toIban = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $amount = null;
}
