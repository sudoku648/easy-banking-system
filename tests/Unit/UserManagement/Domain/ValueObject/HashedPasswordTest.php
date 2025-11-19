<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\TestCase;

final class HashedPasswordTest extends TestCase
{
    public function testConstructorCreatesValidHashedPassword(): void
    {
        $hash = '$2y$13$hashedpassword';
        $hashedPassword = new HashedPassword($hash);

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
        $hashedPassword1 = new HashedPassword($hash);
        $hashedPassword2 = new HashedPassword($hash);

        self::assertTrue($hashedPassword1->equals($hashedPassword2));
    }

    public function testEqualsReturnsFalseForDifferentHashedPasswords(): void
    {
        $hashedPassword1 = new HashedPassword('$2y$13$hashedpassword1');
        $hashedPassword2 = new HashedPassword('$2y$13$hashedpassword2');

        self::assertFalse($hashedPassword1->equals($hashedPassword2));
    }
}
