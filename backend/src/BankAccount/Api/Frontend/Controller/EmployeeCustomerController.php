<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeCustomerController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[Route('/customers/{customerId}', name: 'api_employee_customer', methods: ['GET'])]
    public function __invoke(string $customerId): JsonResponse
    {
        try {
            $user = $this->userRepository->findById(UserId::fromString($customerId));

            if (null === $user) {
                return new UnprocessableEntityResponse(
                    message: 'Customer not found',
                )->toJsonResponse();
            }

            if (!$user instanceof Customer) {
                return new UnprocessableEntityResponse(
                    message: 'User is not a customer',
                )->toJsonResponse();
            }

            $permanentResidence = $user->getPermanentResidenceAddress();
            $correspondenceAddresses = $user->getCorrespondenceAddresses();

            $customerData = [
                'id' => $user->id->getValue(),
                'username' => $user->username->getValue(),
                'firstName' => $user->firstName->getValue(),
                'lastName' => $user->lastName->getValue(),
                'permanentResidence' => null !== $permanentResidence ? [
                    'street' => $permanentResidence->address->street->getValue(),
                    'city' => $permanentResidence->address->city->getValue(),
                    'postalCode' => $permanentResidence->address->postalCode->getValue(),
                    'country' => $permanentResidence->address->country->getValue(),
                ] : null,
                'correspondenceAddresses' => array_map(
                    fn ($customerAddress): array => [
                        'street' => $customerAddress->address->street->getValue(),
                        'city' => $customerAddress->address->city->getValue(),
                        'postalCode' => $customerAddress->address->postalCode->getValue(),
                        'country' => $customerAddress->address->country->getValue(),
                    ],
                    $correspondenceAddresses,
                ),
            ];

            return new ApiSuccessResponse(
                message: 'Customer retrieved successfully',
                data: ['customer' => $customerData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
