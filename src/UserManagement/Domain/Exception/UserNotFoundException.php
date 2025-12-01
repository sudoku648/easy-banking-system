<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public static function withId(string $userId): self
    {
        return new self(\sprintf('User with ID "%s" not found', $userId));
    }

    public static function withUsername(string $username): self
    {
        return new self(\sprintf('User with username "%s" not found', $username));
    }
}
