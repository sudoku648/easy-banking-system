<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Domain\ValueObject;

use App\BankAccount\Domain\ValueObject\CustomerId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class CustomerIdTest extends TestCase
{
    public function testConstructorCreatesValidCustomerId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $customerId = CustomerId::fromString($uuidString);

        self::assertSame($uuidString, $customerId->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        CustomerId::fromString('invalid-uuid');
    }

    public function testGenerateCreatesValidCustomerId(): void
    {
        $customerId = CustomerId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $customerId->getValue(),
        );
    }

    public function testEqualsReturnsTrueForSameCustomerId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $customerId1 = CustomerId::fromString($uuidString);
        $customerId2 = CustomerId::fromString($uuidString);

        self::assertTrue($customerId1->equals($customerId2));
    }

    public function testEqualsReturnsFalseForDifferentCustomerIds(): void
    {
        $customerId1 = CustomerId::fromString('123e4567-e89b-12d3-a456-426614174000');
        $customerId2 = CustomerId::fromString('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($customerId1->equals($customerId2));
    }
}
