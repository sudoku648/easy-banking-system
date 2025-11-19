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
        $lastName = new LastName('Doe');

        self::assertSame('Doe', $lastName->getValue());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $lastName = new LastName('  Doe  ');

        self::assertSame('Doe', $lastName->getValue());
    }

    public function testConstructorThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        new LastName('');
    }

    public function testConstructorThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        new LastName('   ');
    }

    public function testConstructorThrowsExceptionForTooShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name must be at least 2 characters long');

        new LastName('D');
    }

    public function testConstructorThrowsExceptionForTooLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be longer than 50 characters');

        new LastName(str_repeat('a', 51));
    }

    public function testConstructorAcceptsMinimumLength(): void
    {
        $lastName = new LastName('Do');

        self::assertSame('Do', $lastName->getValue());
    }

    public function testConstructorAcceptsMaximumLength(): void
    {
        $longName = str_repeat('a', 50);
        $lastName = new LastName($longName);

        self::assertSame($longName, $lastName->getValue());
    }

    public function testEqualsReturnsTrueForSameLastName(): void
    {
        $lastName1 = new LastName('Doe');
        $lastName2 = new LastName('Doe');

        self::assertTrue($lastName1->equals($lastName2));
    }

    public function testEqualsReturnsFalseForDifferentLastNames(): void
    {
        $lastName1 = new LastName('Doe');
        $lastName2 = new LastName('Smith');

        self::assertFalse($lastName1->equals($lastName2));
    }
}
