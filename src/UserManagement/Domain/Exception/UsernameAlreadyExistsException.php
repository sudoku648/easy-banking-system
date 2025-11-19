<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Exception;

final class UsernameAlreadyExistsException extends \DomainException
{
    public static function forUsername(string $username): self
    {
        return new self(\sprintf('Username "%s" already exists', $username));
    }
}
