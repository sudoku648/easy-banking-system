<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

final class InvalidPasswordException extends ValidationException
{
    public static function tooShort(int $minLength): self
    {
        return new self(\sprintf('Password must be at least %d characters long', $minLength));
    }

    public static function incorrectCurrentPassword(): self
    {
        return new self('Current password is incorrect');
    }
}
