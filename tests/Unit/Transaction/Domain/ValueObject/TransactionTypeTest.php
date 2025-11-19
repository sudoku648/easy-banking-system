<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Domain\ValueObject;

use App\Transaction\Domain\ValueObject\TransactionType;
use PHPUnit\Framework\TestCase;

final class TransactionTypeTest extends TestCase
{
    public function testFromStringCreatesValidTransactionType(): void
    {
        $type = TransactionType::fromString('TRANSFER_WITHDRAWAL');

        self::assertSame(TransactionType::TRANSFER_WITHDRAWAL, $type);
    }

    public function testFromStringThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\ValueError::class);

        TransactionType::fromString('INVALID_TYPE');
    }

    public function testTransactionTypeHasCorrectValues(): void
    {
        self::assertSame('TRANSFER_WITHDRAWAL', TransactionType::TRANSFER_WITHDRAWAL->value);
        self::assertSame('TRANSFER_DEPOSIT', TransactionType::TRANSFER_DEPOSIT->value);
        self::assertSame('CASH_WITHDRAWAL', TransactionType::CASH_WITHDRAWAL->value);
    }

    public function testAllTransactionTypesAreUnique(): void
    {
        $types = [
            TransactionType::TRANSFER_WITHDRAWAL,
            TransactionType::TRANSFER_DEPOSIT,
            TransactionType::CASH_WITHDRAWAL,
        ];

        $values = array_map(fn (TransactionType $type) => $type->value, $types);

        self::assertSame(count($values), count(array_unique($values)));
    }
}
