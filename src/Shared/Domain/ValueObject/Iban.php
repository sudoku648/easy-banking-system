<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final class Iban extends StringValueObject
{
    private const string IBAN_PATTERN = '/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/';
    private const string POLAND_COUNTRY_CODE = 'PL';

    public function __construct(string $value)
    {
        $value = strtoupper(str_replace(' ', '', $value));
        
        Assert::regex($value, self::IBAN_PATTERN, 'Invalid IBAN format');
        Assert::minLength($value, 15, 'IBAN is too short');
        Assert::maxLength($value, 34, 'IBAN is too long');

        parent::__construct($value);
    }

    public static function generatePolishIban(string $accountNumber): self
    {
        Assert::regex($accountNumber, '/^[0-9]{26}$/', 'Polish account number must be 26 digits');

        $iban = self::POLAND_COUNTRY_CODE . self::calculateCheckDigits($accountNumber) . $accountNumber;

        return new self($iban);
    }

    public function getCountryCode(): string
    {
        return substr($this->value, 0, 2);
    }

    public function getCheckDigits(): string
    {
        return substr($this->value, 2, 2);
    }

    public function getAccountNumber(): string
    {
        return substr($this->value, 4);
    }

    private static function calculateCheckDigits(string $accountNumber): string
    {
        $rearranged = $accountNumber . self::POLAND_COUNTRY_CODE . '00';
        $numericString = self::convertToNumeric($rearranged);
        $remainder = bcmod($numericString, '97');
        $checkDigits = 98 - (int) $remainder;

        return str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT);
    }

    private static function convertToNumeric(string $iban): string
    {
        return strtr(
            $iban,
            [
                'A' => '10', 'B' => '11', 'C' => '12', 'D' => '13', 'E' => '14', 'F' => '15',
                'G' => '16', 'H' => '17', 'I' => '18', 'J' => '19', 'K' => '20', 'L' => '21',
                'M' => '22', 'N' => '23', 'O' => '24', 'P' => '25', 'Q' => '26', 'R' => '27',
                'S' => '28', 'T' => '29', 'U' => '30', 'V' => '31', 'W' => '32', 'X' => '33',
                'Y' => '34', 'Z' => '35',
            ],
        );
    }
}
