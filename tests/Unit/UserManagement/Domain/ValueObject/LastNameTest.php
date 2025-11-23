<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\LastName;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class LastNameTest extends TestCase
{
    public function testConstructorCreatesValidLastName(): void
    {
        $lastName = LastName::fromString('Doe');

        self::assertSame('Doe', $lastName->getValue());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $lastName = LastName::fromString('  Doe  ');

        self::assertSame('Doe', $lastName->getValue());
    }

    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        LastName::fromString('');
    }

    public function testConstructorThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        LastName::fromString('   ');
    }

    public function testConstructorThrowsExceptionForTooShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name must be at least 2 characters long');

        LastName::fromString('D');
    }

    public function testConstructorThrowsExceptionForTooLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be longer than 50 characters');

        LastName::fromString(str_repeat('a', 51));
    }

    public function testConstructorAcceptsMinimumLength(): void
    {
        $lastName = LastName::fromString('Do');

        self::assertSame('Do', $lastName->getValue());
    }

    public function testConstructorAcceptsMaximumLength(): void
    {
        $longName = str_repeat('a', 50);
        $lastName = LastName::fromString($longName);

        self::assertSame($longName, $lastName->getValue());
    }

    public function testEqualsReturnsTrueForSameLastName(): void
    {
        $lastName1 = LastName::fromString('Doe');
        $lastName2 = LastName::fromString('Doe');

        self::assertTrue($lastName1->equals($lastName2));
    }

    public function testEqualsReturnsFalseForDifferentLastNames(): void
    {
        $lastName1 = LastName::fromString('Doe');
        $lastName2 = LastName::fromString('Smith');

        self::assertFalse($lastName1->equals($lastName2));
    }
}
