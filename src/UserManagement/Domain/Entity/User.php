<?php

declare(strict_types=1);

namespace App\UserManagement\Domain\Entity;

use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\Locale;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Domain\ValueObject\UserRole;

abstract class User
{
    protected function __construct(
        public readonly UserId $id,
        public private(set) Username $username,
        public private(set) HashedPassword $password,
        public private(set) FirstName $firstName,
        public private(set) LastName $lastName,
        public private(set) bool $isActive = true,
        public private(set) Locale $locale = Locale::POLISH,
    ) {
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
    public static function fromRaw(array $data): self
    {
        return new static(
            UserId::fromString($data['id']),
            Username::fromString($data['username']),
            HashedPassword::fromString($data['password']),
            FirstName::fromString($data['first_name']),
            LastName::fromString($data['last_name']),
            (bool) $data['is_active'],
            Locale::from($data['locale']),
        );
    }

    abstract public function getRole(): UserRole;

    public function getFullName(): string
    {
        return \sprintf('%s %s', $this->firstName->getValue(), $this->lastName->getValue());
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function changeLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }
}
