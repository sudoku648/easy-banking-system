<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\ValueObject\Locale;
use App\UserManagement\Domain\ValueObject\UserId;

final readonly class ChangeUserLocaleCommand
{
    public function __construct(
        public UserId $userId,
        public Locale $locale,
    ) {
    }
}
