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
        $firstName = new FirstName('John');

        self::assertSame('John', $firstName->getValue());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $firstName = new FirstName('  John  ');

        self::assertSame('John', $firstName->getValue());
    }

    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        new FirstName('');
    }

    public function testConstructorThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        new FirstName('   ');
    }

    public function testConstructorThrowsExceptionForTooShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name must be at least 2 characters long');

        new FirstName('J');
    }

    public function testConstructorThrowsExceptionForTooLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be longer than 50 characters');

        new FirstName(str_repeat('a', 51));
    }

    public function testConstructorAcceptsMinimumLength(): void
    {
        $firstName = new FirstName('Jo');

        self::assertSame('Jo', $firstName->getValue());
    }

    public function testConstructorAcceptsMaximumLength(): void
    {
        $longName = str_repeat('a', 50);
        $firstName = new FirstName($longName);

        self::assertSame($longName, $firstName->getValue());
    }

    public function testEqualsReturnsTrueForSameFirstName(): void
    {
        $firstName1 = new FirstName('John');
        $firstName2 = new FirstName('John');

        self::assertTrue($firstName1->equals($firstName2));
    }

    public function testEqualsReturnsFalseForDifferentFirstNames(): void
    {
        $firstName1 = new FirstName('John');
        $firstName2 = new FirstName('Jane');

        self::assertFalse($firstName1->equals($firstName2));
    }
}
