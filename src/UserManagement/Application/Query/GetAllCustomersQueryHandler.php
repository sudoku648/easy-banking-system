<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Query;

use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;

final readonly class GetAllCustomersQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return array<array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}>
     */
    public function __invoke(GetAllCustomersQuery $query): array
    {
        /** @var array<Customer> $customers */
        $customers = $this->userRepository->findAllCustomers();

        return array_values(array_map(
            fn (Customer $customer): array => [
                'id' => $customer->id->getValue(),
                'username' => $customer->username->getValue(),
                'firstName' => $customer->firstName->getValue(),
                'lastName' => $customer->lastName->getValue(),
                'fullName' => $customer->getFullName(),
                'isActive' => $customer->isActive,
            ],
            $customers,
        ));
    }
}
