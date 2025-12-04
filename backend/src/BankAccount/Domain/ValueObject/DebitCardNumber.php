<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use Webmozart\Assert\Assert;

final class DebitCardNumber extends StringValueObject
{
    protected function __construct(string $value)
    {
        // Remove any spaces or dashes
        $cleanValue = (string) preg_replace('/[\s\-]/', '', $value);

        Assert::regex($cleanValue, '/^\d{16}$/', 'Debit card number must be exactly 16 digits');

        parent::__construct($cleanValue);
    }

    public function getMasked(): string
    {
        return '****-****-****-' . substr($this->value, -4);
    }
}
