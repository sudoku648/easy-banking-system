<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class UserIdTest extends TestCase
{
    public function testConstructorCreatesValidUserId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $userId = UserId::fromString($uuidString);

        self::assertSame($uuidString, $userId->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        UserId::fromString('invalid-uuid');
    }

    public function testGenerateCreatesValidUserId(): void
    {
        $userId = UserId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $userId->getValue(),
        );
    }

    public function testEqualsReturnsTrueForSameUserId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $userId1 = UserId::fromString($uuidString);
        $userId2 = UserId::fromString($uuidString);

        self::assertTrue($userId1->equals($userId2));
    }

    public function testEqualsReturnsFalseForDifferentUserIds(): void
    {
        $userId1 = UserId::fromString('123e4567-e89b-12d3-a456-426614174000');
        $userId2 = UserId::fromString('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($userId1->equals($userId2));
    }
}
