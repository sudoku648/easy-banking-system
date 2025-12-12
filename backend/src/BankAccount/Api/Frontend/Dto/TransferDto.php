<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class TransferDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $sourceAccountId,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Iban]
        public string $recipientIban,
        #[Assert\Type(type: 'integer')]
        #[Assert\NotBlank]
        #[Assert\GreaterThan(0)]
        public int $amount,
    ) {
    }
}
