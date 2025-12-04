<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\Exception\InvalidPasswordException;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\TestCase;

final class HashedPasswordTest extends TestCase
{
    public function testConstructorCreatesValidHashedPassword(): void
    {
        $hash = '$2y$13$hashedpassword';
        $hashedPassword = HashedPassword::fromString($hash);

        self::assertSame($hash, $hashedPassword->getValue());
    }

    public function testFromPlainPasswordCreatesHashedPassword(): void
    {
        $plainPassword = 'MySecurePassword123';

        $hashedPassword = HashedPassword::fromPlainPassword($plainPassword);

        self::assertNotSame($plainPassword, $hashedPassword->getValue());
        self::assertStringStartsWith('$2y$', $hashedPassword->getValue());
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $plainPassword = 'MySecurePassword123';
        $hashedPassword = HashedPassword::fromPlainPassword($plainPassword);

        self::assertTrue($hashedPassword->verify($plainPassword));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $plainPassword = 'MySecurePassword123';
        $hashedPassword = HashedPassword::fromPlainPassword($plainPassword);

        self::assertFalse($hashedPassword->verify('WrongPassword'));
    }

    public function testFromPlainPasswordCreatesDifferentHashesForSamePassword(): void
    {
        $plainPassword = 'MySecurePassword123';

        $hashedPassword1 = HashedPassword::fromPlainPassword($plainPassword);
        $hashedPassword2 = HashedPassword::fromPlainPassword($plainPassword);

        self::assertNotSame($hashedPassword1->getValue(), $hashedPassword2->getValue());
    }

    public function testEqualsReturnsTrueForSameHashedPassword(): void
    {
        $hash = '$2y$13$hashedpassword';
        $hashedPassword1 = HashedPassword::fromString($hash);
        $hashedPassword2 = HashedPassword::fromString($hash);

        self::assertTrue($hashedPassword1->equals($hashedPassword2));
    }

    public function testEqualsReturnsFalseForDifferentHashedPasswords(): void
    {
        $hashedPassword1 = HashedPassword::fromString('$2y$13$hashedpassword1');
        $hashedPassword2 = HashedPassword::fromString('$2y$13$hashedpassword2');

        self::assertFalse($hashedPassword1->equals($hashedPassword2));
    }

    public function testFromPlainPasswordThrowsExceptionForTooShortPassword(): void
    {
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        HashedPassword::fromPlainPassword('short');
    }

    public function testFromPlainPasswordAcceptsMinimumLengthPassword(): void
    {
        $hashedPassword = HashedPassword::fromPlainPassword('12345678'); // Exactly 8 characters

        self::assertTrue($hashedPassword->verify('12345678'));
    }

    public function testFromPlainPasswordThrowsExceptionForEmptyPassword(): void
    {
        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        HashedPassword::fromPlainPassword('');
    }
}
