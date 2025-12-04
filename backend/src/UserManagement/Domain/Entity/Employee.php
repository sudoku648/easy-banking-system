<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Entity;

use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;

final class Employee extends User
{
    public static function create(
        UserId $id,
        Username $username,
        HashedPassword $password,
        FirstName $firstName,
        LastName $lastName,
    ): self {
        return new self(
            $id,
            $username,
            $password,
            $firstName,
            $lastName,
        );
    }

    public function getRole(): UserRole
    {
        return UserRole::EMPLOYEE;
    }
}
