<?php

declare(strict_types=1);

namespace App\Tests\Unit\Transaction\Domain\ValueObject;

use App\Transaction\Domain\ValueObject\BankAccountId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class BankAccountIdTest extends TestCase
{
    public function testConstructorCreatesValidBankAccountId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $bankAccountId = BankAccountId::fromString($uuidString);

        self::assertSame($uuidString, $bankAccountId->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        BankAccountId::fromString('invalid-uuid');
    }

    public function testGenerateCreatesValidBankAccountId(): void
    {
        $bankAccountId = BankAccountId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $bankAccountId->getValue(),
        );
    }

    public function testEqualsReturnsTrueForSameBankAccountId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $bankAccountId1 = BankAccountId::fromString($uuidString);
        $bankAccountId2 = BankAccountId::fromString($uuidString);

        self::assertTrue($bankAccountId1->equals($bankAccountId2));
    }

    public function testEqualsReturnsFalseForDifferentBankAccountIds(): void
    {
        $bankAccountId1 = BankAccountId::fromString('123e4567-e89b-12d3-a456-426614174000');
        $bankAccountId2 = BankAccountId::fromString('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($bankAccountId1->equals($bankAccountId2));
    }
}
