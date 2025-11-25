<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\ValueObject\UserId;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public UserId $userId,
        #[\SensitiveParameter]
        public string $currentPassword,
        #[\SensitiveParameter]
        public string $newPassword,
    ) {
    }
}
