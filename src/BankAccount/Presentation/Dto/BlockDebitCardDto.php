<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BlockDebitCardDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $debitCardId = null;
}
