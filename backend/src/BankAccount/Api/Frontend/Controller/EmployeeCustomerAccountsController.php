<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
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
final class EmployeeCustomerAccountsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/customers/{customerId}/accounts', name: 'api_employee_customer_accounts', methods: ['GET'])]
    public function __invoke(string $customerId): JsonResponse
    {
        try {
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($customerId),
            );

            $accountsData = array_map(
                fn (BankAccount $account): array => [
                    'id' => $account->id->getValue(),
                    'iban' => $account->iban->getValue(),
                    'balance' => $account->balance->getAmount(),
                    'blockedAmount' => $account->blockedAmount->getAmount(),
                    'availableBalance' => $account->getAvailableBalance()->getAmount(),
                    'currency' => $account->balance->getCurrency()->value,
                    'isActive' => $account->isActive,
                ],
                $accounts,
            );

            return new ApiSuccessResponse(
                message: 'Accounts retrieved successfully',
                data: ['accounts' => $accountsData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
