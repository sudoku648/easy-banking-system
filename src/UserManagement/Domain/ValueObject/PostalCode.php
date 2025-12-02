<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use Webmozart\Assert\Assert;

final class PostalCode extends StringValueObject
{
    private const string PATTERN = '/^[0-9]{2}-[0-9]{3}$/';

    protected function __construct(string $value)
    {
        $value = trim($value);

        Assert::notEmpty($value, 'Postal code cannot be empty');
        Assert::regex($value, self::PATTERN, 'Postal code must be in format XX-XXX');

        parent::__construct($value);
    }
}
