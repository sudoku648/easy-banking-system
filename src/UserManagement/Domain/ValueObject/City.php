<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use Webmozart\Assert\Assert;

final class City extends StringValueObject
{
    private const int MIN_LENGTH = 2;
    private const int MAX_LENGTH = 50;

    protected function __construct(string $value)
    {
        $value = trim($value);

        Assert::notEmpty($value, 'City cannot be empty');
        Assert::minLength($value, self::MIN_LENGTH, 'City must be at least %2$s characters long');
        Assert::maxLength($value, self::MAX_LENGTH, 'City cannot be longer than %2$s characters');

        parent::__construct($value);
    }
}
