<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;

final class HashedPassword extends StringValueObject
{
    public static function fromPlainPassword(string $plainPassword): self
    {
        return new self(password_hash($plainPassword, PASSWORD_BCRYPT));
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }
}
