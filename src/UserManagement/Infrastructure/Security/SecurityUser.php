<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Security;

use App\UserManagement\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getUsername()->getValue();
    }

    public function getRoles(): array
    {
        $role = $this->user->getRole();

        return match (true) {
            $role->isEmployee() => ['ROLE_EMPLOYEE', 'ROLE_USER'],
            $role->isCustomer() => ['ROLE_CUSTOMER', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function getPassword(): string
    {
        return $this->user->getPassword()->getValue();
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase
    }
}
