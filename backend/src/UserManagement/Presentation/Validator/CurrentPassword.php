<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class CurrentPassword extends Constraint
{
    public string $message = 'user.current_password_incorrect';

    public function getDefaultOption(): string
    {
        return 'message';
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
