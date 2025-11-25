<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

final readonly class CreateCustomerCommand
{
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password,
        public string $firstName,
        public string $lastName,
    ) {
    }
}
