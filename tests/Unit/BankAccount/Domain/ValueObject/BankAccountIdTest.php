<?php

declare(strict_types=1);

namespace App\Tests\Unit\BankAccount\Domain\ValueObject;

use App\BankAccount\Domain\ValueObject\BankAccountId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class BankAccountIdTest extends TestCase
{
    public function testConstructorCreatesValidBankAccountId(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $bankAccountId = new BankAccountId($uuidString);

        self::assertSame($uuidString, $bankAccountId->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        new BankAccountId('invalid-uuid');
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
        $bankAccountId1 = new BankAccountId($uuidString);
        $bankAccountId2 = new BankAccountId($uuidString);

        self::assertTrue($bankAccountId1->equals($bankAccountId2));
    }

    public function testEqualsReturnsFalseForDifferentBankAccountIds(): void
    {
        $bankAccountId1 = new BankAccountId('123e4567-e89b-12d3-a456-426614174000');
        $bankAccountId2 = new BankAccountId('123e4567-e89b-12d3-a456-426614174001');

        self::assertFalse($bankAccountId1->equals($bankAccountId2));
    }
}
