<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Security;

use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\Username;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class DatabaseUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new \InvalidArgumentException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $username = new Username($user->getUserIdentifier());
        $refreshedUser = $this->userRepository->findByUsername($username);

        if ($refreshedUser === null) {
            throw new UserNotFoundException(\sprintf('User with username "%s" not found.', $user->getUserIdentifier()));
        }

        return new SecurityUser($refreshedUser);
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $username = new Username($identifier);
        $user = $this->userRepository->findByUsername($username);

        if ($user === null || !$user->isActive()) {
            throw new UserNotFoundException(\sprintf('User with username "%s" not found.', $identifier));
        }

        return new SecurityUser($user);
    }
}
