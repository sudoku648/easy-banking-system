<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeActiveAccountsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/active-accounts', name: 'api_employee_active_accounts', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
            $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

            return new ApiSuccessResponse(
                message: 'Active accounts retrieved successfully',
                data: $accounts,
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
