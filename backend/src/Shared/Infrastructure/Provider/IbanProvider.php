<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Provider;

use App\Shared\Domain\Provider\IbanProviderInterface;
use App\Shared\Domain\ValueObject\Iban;

final readonly class IbanProvider implements IbanProviderInterface
{
    private const string BANK_CODE = '10201026';

    public function isInternalIban(Iban $iban): bool
    {
        // Polish IBAN format: PL + 2 check digits + 8 bank code + 16 account number
        if ($iban->getCountryCode() !== 'PL') {
            return false;
        }

        $accountNumber = $iban->getAccountNumber();
        $bankCode = substr($accountNumber, 0, 8);

        return self::BANK_CODE === $bankCode;
    }

    public function getBankCode(): string
    {
        return self::BANK_CODE;
    }
}
