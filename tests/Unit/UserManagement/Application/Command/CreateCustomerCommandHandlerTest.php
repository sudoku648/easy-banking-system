<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Application\Command;

use App\Tests\Support\AddressTestHelper;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Application\Command\CreateCustomerCommandHandler;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Exception\UsernameAlreadyExistsException;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class CreateCustomerCommandHandlerTest extends TestCase
{
    use AddressTestHelper;

    public function testHandleCreatesCustomerWithPermanentAddress(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $userId = UserId::generate();

        $repository
            ->expects(self::once())
            ->method('existsByUsername')
            ->with(self::callback(fn (Username $username): bool => $username->getValue() === 'john.doe'))
            ->willReturn(false);

        $repository
            ->expects(self::once())
            ->method('nextIdentity')
            ->willReturn($userId);

        $repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Customer $customer): bool {
                self::assertSame('john.doe', $customer->username->getValue());
                self::assertSame('John', $customer->firstName->getValue());
                self::assertSame('Doe', $customer->lastName->getValue());

                $permanentAddress = $customer->getPermanentResidenceAddress();
                self::assertNotNull($permanentAddress);
                self::assertSame('Main Street 123', $permanentAddress->address->street->getValue());
                self::assertSame('Warsaw', $permanentAddress->address->city->getValue());
                self::assertSame('00-001', $permanentAddress->address->postalCode->getValue());
                self::assertSame('Poland', $permanentAddress->address->country->getValue());

                return true;
            }));

        $handler = new CreateCustomerCommandHandler($repository);

        $command = new CreateCustomerCommand(
            username: 'john.doe',
            password: 'SecurePass123',
            firstName: 'John',
            lastName: 'Doe',
            permanentResidenceStreet: 'Main Street 123',
            permanentResidenceCity: 'Warsaw',
            permanentResidencePostalCode: '00-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [
                [
                    'street' => 'Main Street 123',
                    'city' => 'Warsaw',
                    'postalCode' => '00-001',
                    'country' => 'Poland',
                ],
            ],
        );

        $handler($command);
    }

    public function testHandleCreatesCustomerWithCorrespondenceAddress(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $userId = UserId::generate();

        $repository
            ->method('existsByUsername')
            ->willReturn(false);

        $repository
            ->method('nextIdentity')
            ->willReturn($userId);

        $repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Customer $customer): bool {
                $correspondenceAddresses = array_values($customer->getCorrespondenceAddresses());
                self::assertCount(1, $correspondenceAddresses);
                self::assertSame('Office Street 456', $correspondenceAddresses[0]->address->street->getValue());
                self::assertSame('Krakow', $correspondenceAddresses[0]->address->city->getValue());

                return true;
            }));

        $handler = new CreateCustomerCommandHandler($repository);

        $command = new CreateCustomerCommand(
            username: 'jane.smith',
            password: 'SecurePass456',
            firstName: 'Jane',
            lastName: 'Smith',
            permanentResidenceStreet: 'Home Street 789',
            permanentResidenceCity: 'Gdansk',
            permanentResidencePostalCode: '80-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [
                [
                    'street' => 'Office Street 456',
                    'city' => 'Krakow',
                    'postalCode' => '30-001',
                    'country' => 'Poland',
                ],
            ],
        );

        $handler($command);
    }

    public function testHandleThrowsExceptionWhenUsernameExists(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('existsByUsername')
            ->willReturn(true);

        $repository
            ->expects(self::never())
            ->method('save');

        $handler = new CreateCustomerCommandHandler($repository);

        $command = new CreateCustomerCommand(
            username: 'existing.user',
            password: 'SecurePass123',
            firstName: 'John',
            lastName: 'Doe',
            permanentResidenceStreet: 'Main Street 123',
            permanentResidenceCity: 'Warsaw',
            permanentResidencePostalCode: '00-001',
            permanentResidenceCountry: 'Poland',
            correspondenceAddresses: [
                [
                    'street' => 'Main Street 123',
                    'city' => 'Warsaw',
                    'postalCode' => '00-001',
                    'country' => 'Poland',
                ],
            ],
        );

        $this->expectException(UsernameAlreadyExistsException::class);

        $handler($command);
    }
}
