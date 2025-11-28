<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class IssueDebitCardDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $bankAccountId = null;
}
