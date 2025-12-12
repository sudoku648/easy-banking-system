<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Transaction\Application\Query\GetTransactionHistoryQuery;
use App\Transaction\Domain\Entity\Transaction;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerAccountTransactionsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/accounts/{accountId}/transactions', name: 'api_customer_transactions', methods: ['GET'])]
    public function __invoke(string $accountId): JsonResponse
    {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $accounts);
            if (!\in_array($accountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found or does not belong to you',
                )->toJsonResponse();
            }

            /** @var array<Transaction> $transactions */
            $transactions = $this->handle(
                new GetTransactionHistoryQuery($accountId),
            );

            $transactionsData = array_map(
                fn (Transaction $transaction): array => [
                    'id' => $transaction->id->getValue(),
                    'type' => $transaction->type->value,
                    'amount' => $transaction->amount->getAmount(),
                    'currency' => $transaction->amount->getCurrency()->value,
                    'originalAmount' => $transaction->originalAmount->getAmount(),
                    'originalCurrency' => $transaction->originalAmount->getCurrency()->value,
                    'exchangeRate' => $transaction->exchangeRate->getRate(),
                    'status' => $transaction->status->value,
                    'occurredAt' => $transaction->occurredAt->format('Y-m-d H:i:s'),
                ],
                $transactions,
            );

            return new ApiSuccessResponse(
                message: 'Transactions retrieved successfully',
                data: ['transactions' => $transactionsData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
