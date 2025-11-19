<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class UsernameTest extends TestCase
{
    public function testConstructorCreatesValidUsername(): void
    {
        $username = new Username('john.doe');

        self::assertSame('john.doe', $username->getValue());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $username = new Username('  john.doe  ');

        self::assertSame('john.doe', $username->getValue());
    }

    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new Username('');
    }

    public function testConstructorThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new Username('   ');
    }

    public function testConstructorThrowsExceptionForTooShortUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must be at least 3 characters long');

        new Username('jo');
    }

    public function testConstructorThrowsExceptionForTooLongUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be longer than 50 characters');

        new Username(str_repeat('a', 51));
    }

    public function testConstructorAcceptsMinimumLength(): void
    {
        $username = new Username('joe');

        self::assertSame('joe', $username->getValue());
    }

    public function testConstructorAcceptsMaximumLength(): void
    {
        $longUsername = str_repeat('a', 50);
        $username = new Username($longUsername);

        self::assertSame($longUsername, $username->getValue());
    }

    public function testEqualsReturnsTrueForSameUsername(): void
    {
        $username1 = new Username('john.doe');
        $username2 = new Username('john.doe');

        self::assertTrue($username1->equals($username2));
    }

    public function testEqualsReturnsFalseForDifferentUsernames(): void
    {
        $username1 = new Username('john.doe');
        $username2 = new Username('jane.smith');

        self::assertFalse($username1->equals($username2));
    }
}
