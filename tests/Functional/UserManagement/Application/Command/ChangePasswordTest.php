<?php

declare(strict_types=1);

namespace App\Tests\Functional\UserManagement\Application\Command;

use App\Tests\Shared\ApplicationTestCase;
use App\UserManagement\Application\Command\ChangePasswordCommand;
use App\UserManagement\Application\Command\ChangePasswordCommandHandler;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Application\Command\CreateCustomerCommandHandler;
use App\UserManagement\Domain\Exception\InvalidPasswordException;
use App\UserManagement\Domain\Exception\UserNotFoundException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;

final class ChangePasswordTest extends ApplicationTestCase
{
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testChangePasswordUpdatesUserPassword(): void
    {
        $createHandler = new CreateCustomerCommandHandler($this->userRepository);
        $createCommand = new CreateCustomerCommand(
            username: 'john.doe',
            password: 'OldPassword123!',
            firstName: 'John',
            lastName: 'Doe',
            permanentResidenceStreet: 'Main Street 123',
            permanentResidenceCity: 'Warsaw',
            permanentResidencePostalCode: '00-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [['street' => 'Main Street 123', 'city' => 'Warsaw', 'postalCode' => '00-001', 'country' => 'Poland']],
        );
        $createHandler($createCommand);

        $user = $this->userRepository->findByUsername(Username::fromString('john.doe'));
        self::assertNotNull($user);
        self::assertTrue($user->password->verify('OldPassword123!'));

        $changeHandler = new ChangePasswordCommandHandler($this->userRepository);
        $changeCommand = new ChangePasswordCommand(
            userId: $user->id,
            currentPassword: 'OldPassword123!',
            newPassword: 'NewPassword456!',
        );
        $changeHandler($changeCommand);

        $updatedUser = $this->userRepository->findByUsername(Username::fromString('john.doe'));
        self::assertNotNull($updatedUser);
        self::assertFalse($updatedUser->password->verify('OldPassword123!'));
        self::assertTrue($updatedUser->password->verify('NewPassword456!'));
    }

    public function testChangePasswordThrowsExceptionForInvalidCurrentPassword(): void
    {
        $createHandler = new CreateCustomerCommandHandler($this->userRepository);
        $createCommand = new CreateCustomerCommand(
            username: 'jane.smith',
            password: 'CorrectPassword123!',
            firstName: 'Jane',
            lastName: 'Smith',
            permanentResidenceStreet: 'Main Street 123',
            permanentResidenceCity: 'Warsaw',
            permanentResidencePostalCode: '00-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [['street' => 'Main Street 123', 'city' => 'Warsaw', 'postalCode' => '00-001', 'country' => 'Poland']],
        );
        $createHandler($createCommand);

        $user = $this->userRepository->findByUsername(Username::fromString('jane.smith'));
        self::assertNotNull($user);

        $changeHandler = new ChangePasswordCommandHandler($this->userRepository);
        $changeCommand = new ChangePasswordCommand(
            userId: $user->id,
            currentPassword: 'WrongPassword123!',
            newPassword: 'NewPassword456!',
        );

        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        $changeHandler($changeCommand);
    }

    public function testChangePasswordThrowsExceptionForNonExistentUser(): void
    {
        $changeHandler = new ChangePasswordCommandHandler($this->userRepository);
        $changeCommand = new ChangePasswordCommand(
            userId: UserId::generate(),
            currentPassword: 'SomePassword123!',
            newPassword: 'NewPassword456!',
        );

        $this->expectException(UserNotFoundException::class);

        $changeHandler($changeCommand);
    }

    public function testChangePasswordAcceptsMinimumLengthPassword(): void
    {
        $createHandler = new CreateCustomerCommandHandler($this->userRepository);
        $createCommand = new CreateCustomerCommand(
            username: 'test.user',
            password: 'InitialPassword!',
            firstName: 'Test',
            lastName: 'User',
            permanentResidenceStreet: 'Main Street 123',
            permanentResidenceCity: 'Warsaw',
            permanentResidencePostalCode: '00-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [['street' => 'Main Street 123', 'city' => 'Warsaw', 'postalCode' => '00-001', 'country' => 'Poland']],
        );
        $createHandler($createCommand);

        $user = $this->userRepository->findByUsername(Username::fromString('test.user'));
        self::assertNotNull($user);

        $changeHandler = new ChangePasswordCommandHandler($this->userRepository);
        $changeCommand = new ChangePasswordCommand(
            userId: $user->id,
            currentPassword: 'InitialPassword!',
            newPassword: '12345678', // Exactly 8 characters
        );
        $changeHandler($changeCommand);

        $updatedUser = $this->userRepository->findByUsername(Username::fromString('test.user'));
        self::assertNotNull($updatedUser);
        self::assertTrue($updatedUser->password->verify('12345678'));
    }
}
