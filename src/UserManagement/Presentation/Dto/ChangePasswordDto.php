<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Dto;

use App\UserManagement\Presentation\Validator\CurrentPassword;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ChangePasswordDto
{
    #[Assert\NotBlank]
    #[CurrentPassword]
    public ?string $currentPassword = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public ?string $newPassword = null;

    #[Assert\NotBlank]
    public ?string $confirmNewPassword = null;

    #[Assert\Callback]
    public function validatePasswordsMatch(ExecutionContextInterface $context): void
    {
        if ($this->newPassword !== null && $this->confirmNewPassword !== null
            && $this->newPassword !== $this->confirmNewPassword) {
            $context->buildViolation('user.passwords_must_match')
                ->atPath('confirmNewPassword')
                ->setTranslationDomain('app')
                ->addViolation();
        }
    }
}
