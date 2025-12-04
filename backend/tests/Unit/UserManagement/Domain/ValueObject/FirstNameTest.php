<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\FirstName;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class FirstNameTest extends TestCase
{
    public function testConstructorCreatesValidFirstName(): void
    {
        $firstName = FirstName::fromString('John');

        self::assertSame('John', $firstName->getValue());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $firstName = FirstName::fromString('  John  ');

        self::assertSame('John', $firstName->getValue());
    }

    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        FirstName::fromString('');
    }

    public function testConstructorThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        FirstName::fromString('   ');
    }

    public function testConstructorThrowsExceptionForTooShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name must be at least 2 characters long');

        FirstName::fromString('J');
    }

    public function testConstructorThrowsExceptionForTooLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be longer than 50 characters');

        FirstName::fromString(str_repeat('a', 51));
    }

    public function testConstructorAcceptsMinimumLength(): void
    {
        $firstName = FirstName::fromString('Jo');

        self::assertSame('Jo', $firstName->getValue());
    }

    public function testConstructorAcceptsMaximumLength(): void
    {
        $longName = str_repeat('a', 50);
        $firstName = FirstName::fromString($longName);

        self::assertSame($longName, $firstName->getValue());
    }

    public function testEqualsReturnsTrueForSameFirstName(): void
    {
        $firstName1 = FirstName::fromString('John');
        $firstName2 = FirstName::fromString('John');

        self::assertTrue($firstName1->equals($firstName2));
    }

    public function testEqualsReturnsFalseForDifferentFirstNames(): void
    {
        $firstName1 = FirstName::fromString('John');
        $firstName2 = FirstName::fromString('Jane');

        self::assertFalse($firstName1->equals($firstName2));
    }
}
