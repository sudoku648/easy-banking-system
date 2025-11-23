<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Persistence\Repository;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\Entity\User;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;
use Doctrine\DBAL\Connection;

final readonly class DbalUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(User $user): void
    {
        $data = [
            'id' => $user->id->getValue(),
            'username' => $user->username->getValue(),
            'password' => $user->password->getValue(),
            'first_name' => $user->firstName->getValue(),
            'last_name' => $user->lastName->getValue(),
            'is_active' => $user->isActive,
            'role' => $user->getRole()->value,
            'locale' => $user->locale->value,
        ];

        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM "user" WHERE id = :id',
            ['id' => $user->id->getValue()],
        );

        if ($exists) {
            $this->connection->update('"user"', $data, ['id' => $user->id->getValue()]);
        } else {
            $this->connection->insert('"user"', $data);
        }
    }

    public function findById(UserId $id): ?User
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM "user" WHERE id = :id',
            ['id' => $id->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findByUsername(Username $username): ?User
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM "user" WHERE username = :username',
            ['username' => $username->getValue()],
        );

        if ($data === false) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findAllCustomers(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM "user" WHERE role = :role ORDER BY last_name, first_name',
            ['role' => UserRole::CUSTOMER->value],
        );

        return array_map(fn (array $data): User => $this->mapToEntity($data), $rows);
    }

    public function existsByUsername(Username $username): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM "user" WHERE username = :username',
            ['username' => $username->getValue()],
        );

        return $count > 0;
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    /**
     * @param array{
     *   id: string,
     *   username: string,
     *   password: string,
     *   first_name: string,
     *   last_name: string,
     *   is_active: bool,
     *   role: string,
     *   locale: string,
     * } $data
     */
    private function mapToEntity(array $data): User
    {
        $role = UserRole::fromString($data['role']);

        return match ($role) {
            UserRole::EMPLOYEE => Employee::fromRaw($data),
            UserRole::CUSTOMER => Customer::fromRaw($data),
        };
    }
}
