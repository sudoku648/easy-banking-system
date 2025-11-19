<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Persistence\Repository;

use App\UserManagement\Domain\Entity\User;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByUsername(Username $username): ?User;

    public function existsByUsername(Username $username): bool;

    public function nextIdentity(): UserId;
}
