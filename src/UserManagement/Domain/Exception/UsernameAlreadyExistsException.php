<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

final class UsernameAlreadyExistsException extends ConflictException
{
    public static function forUsername(string $username): self
    {
        return new self(\sprintf('Username "%s" already exists', $username));
    }
}
