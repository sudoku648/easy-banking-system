<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        public string $currentPassword,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        #[\SensitiveParameter]
        public string $newPassword,
    ) {
    }
}
