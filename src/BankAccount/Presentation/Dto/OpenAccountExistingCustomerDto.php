<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OpenAccountExistingCustomerDto
{
    #[Assert\NotBlank]
    public ?string $customerId = null;

    #[Assert\NotBlank]
    #[Assert\Choice(['PLN', 'EUR', 'USD', 'GBP'])]
    public ?string $currency = null;
}
