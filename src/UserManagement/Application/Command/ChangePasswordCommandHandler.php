<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Exception\InvalidPasswordException;
use App\UserManagement\Domain\Exception\UserNotFoundException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;

final readonly class ChangePasswordCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw new UserNotFoundException(\sprintf('User with ID "%s" not found', $command->userId->getValue()));
        }

        if (!$user->password->verify($command->currentPassword)) {
            throw new InvalidPasswordException('Current password is incorrect');
        }

        $user->changePassword($command->newPassword);

        $this->userRepository->save($user);
    }
}
