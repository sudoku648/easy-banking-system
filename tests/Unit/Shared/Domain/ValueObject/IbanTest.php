<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Iban;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class IbanTest extends TestCase
{
    public function testConstructorCreatesValidIban(): void
    {
        $iban = new Iban('PL61109010140000071219812874');

        self::assertSame('PL61109010140000071219812874', $iban->getValue());
    }

    public function testConstructorNormalizesIbanWithSpaces(): void
    {
        $iban = new Iban('PL61 1090 1014 0000 0712 1981 2874');

        self::assertSame('PL61109010140000071219812874', $iban->getValue());
    }

    public function testConstructorNormalizesLowercaseIban(): void
    {
        $iban = new Iban('pl61109010140000071219812874');

        self::assertSame('PL61109010140000071219812874', $iban->getValue());
    }

    public function testConstructorThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IBAN format');

        new Iban('INVALID');
    }

    public function testConstructorThrowsExceptionForTooShortIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IBAN is too short');

        new Iban('PL6110901014');
    }

    public function testConstructorThrowsExceptionForTooLongIban(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IBAN is too long');

        new Iban('PL611090101400000712198128741234567890123456789');
    }

    public function testGeneratePolishIbanCreatesValidIban(): void
    {
        $accountNumber = '10901014000007121981287400';
        
        $iban = Iban::generatePolishIban($accountNumber);

        self::assertSame('PL', $iban->getCountryCode());
        self::assertSame($accountNumber, $iban->getAccountNumber());
        self::assertSame(2, \strlen($iban->getCheckDigits()));
    }

    public function testGeneratePolishIbanCalculatesCorrectCheckDigits(): void
    {
        $accountNumber = '10901014000007121981287400';
        
        $iban = Iban::generatePolishIban($accountNumber);

        // Verify the generated IBAN is valid by checking the full value
        self::assertSame('PL7810901014000007121981287400', $iban->getValue());
        self::assertSame('78', $iban->getCheckDigits());
    }

    public function testGeneratePolishIbanThrowsExceptionForInvalidAccountNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Polish account number must be 26 digits');

        Iban::generatePolishIban('123');
    }

    public function testGeneratePolishIbanThrowsExceptionForNonNumericAccountNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Polish account number must be 26 digits');

        Iban::generatePolishIban('1090101400000712198128740A');
    }

    public function testGetCountryCodeReturnsCorrectCountryCode(): void
    {
        $iban = new Iban('PL61109010140000071219812874');

        self::assertSame('PL', $iban->getCountryCode());
    }

    public function testGetCheckDigitsReturnsCorrectCheckDigits(): void
    {
        $iban = new Iban('PL61109010140000071219812874');

        self::assertSame('61', $iban->getCheckDigits());
    }

    public function testGetAccountNumberReturnsCorrectAccountNumber(): void
    {
        $iban = new Iban('PL61109010140000071219812874');

        self::assertSame('109010140000071219812874', $iban->getAccountNumber());
    }

    public function testEqualsReturnsTrueForSameIban(): void
    {
        $iban1 = new Iban('PL61109010140000071219812874');
        $iban2 = new Iban('PL61109010140000071219812874');

        self::assertTrue($iban1->equals($iban2));
    }

    public function testEqualsReturnsFalseForDifferentIbans(): void
    {
        $iban1 = new Iban('PL61109010140000071219812874');
        $iban2 = new Iban('PL10105000997603123456789123');

        self::assertFalse($iban1->equals($iban2));
    }
}
