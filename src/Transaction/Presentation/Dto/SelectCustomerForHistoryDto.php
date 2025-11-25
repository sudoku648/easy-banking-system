<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SelectCustomerForHistoryDto
{
    #[Assert\NotBlank(message: 'Please select a customer')]
    public ?string $customerId = null;

    public ?string $bankAccountId = null;
}
