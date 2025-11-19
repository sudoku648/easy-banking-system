<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

final readonly class CreateEmployeeCommand
{
    public function __construct(
        public string $username,
        public string $password,
        public string $firstName,
        public string $lastName,
    ) {
    }
}
