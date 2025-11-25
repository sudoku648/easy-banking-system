<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use App\UserManagement\Domain\Exception\InvalidPasswordException;
use Webmozart\Assert\Assert;

final class HashedPassword extends StringValueObject
{
    private const int MIN_PASSWORD_LENGTH = 8;

    public static function fromPlainPassword(#[\SensitiveParameter] string $plainPassword): self
    {
        self::validatePasswordLength($plainPassword);

        return new self(password_hash($plainPassword, PASSWORD_BCRYPT));
    }

    public function verify(#[\SensitiveParameter] string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }

    private static function validatePasswordLength(#[\SensitiveParameter] string $password): void
    {
        try {
            Assert::minLength($password, self::MIN_PASSWORD_LENGTH);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidPasswordException(
                \sprintf('Password must be at least %d characters long', self::MIN_PASSWORD_LENGTH),
            );
        }
    }
}
