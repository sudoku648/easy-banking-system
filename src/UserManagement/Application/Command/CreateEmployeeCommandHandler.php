<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Command;

use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\Exception\UsernameAlreadyExistsException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\Username;

final readonly class CreateEmployeeCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): void
    {
        $username = new Username($command->username);

        if ($this->userRepository->existsByUsername($username)) {
            throw UsernameAlreadyExistsException::forUsername($username->getValue());
        }

        $employee = Employee::create(
            $this->userRepository->nextIdentity(),
            $username,
            HashedPassword::fromPlainPassword($command->password),
            new FirstName($command->firstName),
            new LastName($command->lastName),
        );

        $this->userRepository->save($employee);
    }
}
