<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerAccountsController extends AbstractController
{
    use HandleTrait;

    #[Route('/accounts', name: 'api_customer_accounts', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
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
