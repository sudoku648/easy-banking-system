<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class UuidTest extends TestCase
{
    public function testConstructorCreatesValidUuid(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = new Uuid($uuidString);

        self::assertSame($uuidString, $uuid->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        new Uuid('invalid-uuid');
    }

    public function testGenerateCreatesValidUuid(): void
    {
        $uuid = Uuid::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid->getValue(),
        );
    }

    public function testGenerateCreatesUniqueUuids(): void
    {
        $uuid1 = Uuid::generate();
        $uuid2 = Uuid::generate();

        self::assertNotEquals($uuid1->getValue(), $uuid2->getValue());
    }

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $uuid1 = new Uuid($uuidString);
        $uuid2 = new Uuid($uuidString);

        self::assertTrue($uuid1->equals($uuid2));
    }

    public function testEqualsReturnsFalseForDifferentUuids(): void
    {
        $uuid1 = new Uuid('123e4567-e89b-12d3-a456-426614174000');
        $uuid2 = new Uuid('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($uuid1->equals($uuid2));
    }

    public function testToStringReturnsUuidValue(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $uuid = new Uuid($uuidString);

        self::assertSame($uuidString, (string) $uuid);
    }
}
