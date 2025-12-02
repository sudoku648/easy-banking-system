<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\Entity;

use App\Tests\Support\AddressTestHelper;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Exception\InvalidPasswordException;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class ChangePasswordTest extends TestCase
{
    use AddressTestHelper;

    public function testChangePasswordUpdatesPassword(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromPlainPassword('OldPassword123'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        self::assertTrue($customer->password->verify('OldPassword123'));
        self::assertFalse($customer->password->verify('NewPassword456'));

        $customer->changePassword('NewPassword456');

        self::assertFalse($customer->password->verify('OldPassword123'));
        self::assertTrue($customer->password->verify('NewPassword456'));
    }

    public function testPasswordVerificationFailsForWrongPassword(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromPlainPassword('CorrectPassword123'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        self::assertFalse($customer->password->verify('WrongPassword123'));
        self::assertTrue($customer->password->verify('CorrectPassword123'));
    }

    public function testChangePasswordThrowsExceptionForTooShortPassword(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromPlainPassword('ValidPassword123'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $customer->changePassword('short');
    }

    public function testChangePasswordAcceptsMinimumLengthPassword(): void
    {
        $customer = Customer::create(
            UserId::generate(),
            Username::fromString('john.doe'),
            HashedPassword::fromPlainPassword('InitialPassword'),
            FirstName::fromString('John'),
            LastName::fromString('Doe'),
            $this->createTestAddress(),
            $this->createTestCorrespondenceAddress(),
        );

        $customer->changePassword('12345678'); // Exactly 8 characters

        self::assertTrue($customer->password->verify('12345678'));
    }
}
