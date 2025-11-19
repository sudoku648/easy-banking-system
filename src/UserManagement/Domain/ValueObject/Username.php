<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use Webmozart\Assert\Assert;

final class Username extends StringValueObject
{
    private const int MIN_LENGTH = 3;
    private const int MAX_LENGTH = 50;

    public function __construct(string $value)
    {
        $value = trim($value);
        
        Assert::notEmpty($value, 'Username cannot be empty');
        Assert::minLength($value, self::MIN_LENGTH, 'Username must be at least %2$s characters long');
        Assert::maxLength($value, self::MAX_LENGTH, 'Username cannot be longer than %2$s characters');

        parent::__construct($value);
    }
}
