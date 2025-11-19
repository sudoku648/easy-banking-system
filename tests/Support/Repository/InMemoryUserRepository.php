<?php

declare(strict_types=1);

namespace App\Tests\Support\Repository;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\User;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User>
     */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->getId()->getValue()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->getValue()] ?? null;
    }

    public function findByUsername(Username $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getUsername()->equals($username)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return User[]
     */
    public function findAllCustomers(): array
    {
        return array_values(
            array_filter(
                $this->users,
                fn (User $user): bool => $user instanceof Customer,
            ),
        );
    }

    public function existsByUsername(Username $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    public function clear(): void
    {
        $this->users = [];
    }
}
