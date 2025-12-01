<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Exception\UserNotFoundException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;

final readonly class ChangeUserLocaleCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ChangeUserLocaleCommand $command): void
    {
        $user = $this->userRepository->findById($command->userId);

        if (null === $user) {
            throw UserNotFoundException::withId($command->userId->getValue());
        }

        $user->changeLocale($command->locale);

        $this->userRepository->save($user);
    }
}
