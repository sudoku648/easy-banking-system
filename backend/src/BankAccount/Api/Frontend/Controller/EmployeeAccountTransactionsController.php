<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Transaction\Application\Query\GetTransactionHistoryQuery;
use App\Transaction\Domain\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeAccountTransactionsController extends AbstractController
{
    use HandleTrait;

    #[Route('/accounts/{accountId}/transactions', name: 'api_employee_transactions', methods: ['GET'])]
    public function __invoke(string $accountId): JsonResponse
    {
        try {
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
