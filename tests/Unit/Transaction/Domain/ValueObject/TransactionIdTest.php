<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Domain\ValueObject;

use App\Transaction\Domain\ValueObject\TransactionId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class TransactionIdTest extends TestCase
{
    public function testConstructorCreatesValidTransactionId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $transactionId = new TransactionId($uuidString);

        self::assertSame($uuidString, $transactionId->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        new TransactionId('invalid-uuid');
    }

    public function testGenerateCreatesValidTransactionId(): void
    {
        $transactionId = TransactionId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $transactionId->getValue(),
        );
    }

    public function testEqualsReturnsTrueForSameTransactionId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $transactionId1 = new TransactionId($uuidString);
        $transactionId2 = new TransactionId($uuidString);

        self::assertTrue($transactionId1->equals($transactionId2));
    }

    public function testEqualsReturnsFalseForDifferentTransactionIds(): void
    {
        $transactionId1 = new TransactionId('123e4567-e89b-12d3-a456-426614174000');
        $transactionId2 = new TransactionId('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($transactionId1->equals($transactionId2));
    }
}
