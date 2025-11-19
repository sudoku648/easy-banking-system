<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CloseBankAccountDto
{
    #[Assert\NotBlank]
    public string $bankAccountId = '';
}
