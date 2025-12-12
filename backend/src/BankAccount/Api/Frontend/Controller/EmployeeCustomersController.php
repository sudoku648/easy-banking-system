<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeCustomersController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/customers', name: 'api_employee_customers', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            /** @var array<array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}> $customers */
            $customers = $this->handle(new GetAllCustomersQuery());

            $customersData = array_map(
                fn (array $customer): array => [
                    'id' => $customer['id'],
                    'username' => $customer['username'],
                    'firstName' => $customer['firstName'],
                    'lastName' => $customer['lastName'],
                    'fullName' => $customer['fullName'],
                ],
                $customers,
            );

            return new ApiSuccessResponse(
                message: 'Customers retrieved successfully',
                data: ['customers' => $customersData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
