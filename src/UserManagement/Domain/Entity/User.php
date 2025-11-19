<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Entity;

use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;

abstract class User
{
    public function __construct(
        private readonly UserId $id,
        private Username $username,
        private HashedPassword $password,
        private FirstName $firstName,
        private LastName $lastName,
        private bool $isActive = true,
    ) {
    }

    abstract public function getRole(): UserRole;

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function getFirstName(): FirstName
    {
        return $this->firstName;
    }

    public function getLastName(): LastName
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return \sprintf('%s %s', $this->firstName->getValue(), $this->lastName->getValue());
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }
}
