<?php

declare(strict_types=1);

namespace App\Transaction\Api\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId as BankAccountIdVO;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/employee/transaction/history/api', name: 'api_employee_transaction_history')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeTransactionHistoryApiController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = $request->query->get('customerId');
        $bankAccountId = $request->query->get('bankAccountId');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 10);

        if (!\in_array($limit, [10, 20, 50], true)) {
            $limit = 10;
        }

        if (null === $customerId && null === $bankAccountId) {
            return new JsonResponse(['error' => 'customerId or bankAccountId is required'], 400);
        }

        $offset = ($page - 1) * $limit;

        try {
            if (null !== $bankAccountId) {
                // View specific bank account history
                $account = $this->bankAccountRepository->findById(
                    BankAccountId::fromString((string) $bankAccountId),
                );

                if (null === $account) {
                    return new JsonResponse(['error' => 'Bank account not found'], 404);
                }

                $total = $this->transactionRepository->countByBankAccountId(
                    BankAccountIdVO::fromString((string) $bankAccountId),
                );

                $transactions = $this->transactionRepository->findByBankAccountIdPaginated(
                    BankAccountIdVO::fromString((string) $bankAccountId),
                    $limit,
                    $offset,
                );

                $accountIban = $account->iban->getValue();

                // Get customer name for display
                $customer = $this->userRepository->findById(
                    UserId::fromString($account->customerId->getValue()),
                );
                $customerName = null !== $customer
                    ? $customer->getFullName() . ' (IBAN: ' . $accountIban . ')'
                    : 'Unknown Customer (IBAN: ' . $accountIban . ')';
            } else {
                // View all customer's accounts history
                /** @var array<BankAccount> $accounts */
                $accounts = $this->handle(
                    new GetBankAccountsByCustomerIdQuery((string) $customerId),
                );

                if (empty($accounts)) {
                    return new JsonResponse([
                        'transactions' => [],
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit,
                        'totalPages' => 0,
                    ]);
                }

                $accountIds = array_map(
                    fn (BankAccount $account): BankAccountIdVO => BankAccountIdVO::fromString($account->id->getValue()),
                    $accounts,
                );

                $total = $this->transactionRepository->countByBankAccountIds($accountIds);
                $transactions = $this->transactionRepository->findByBankAccountIdsPaginated(
                    $accountIds,
                    $limit,
                    $offset,
                );

                $accountIban = null;

                // Get customer name
                $customer = $this->userRepository->findById(
                    UserId::fromString((string) $customerId),
                );
                $customerName = $customer?->getFullName() ?? 'Unknown Customer';
            }

            // Map to array for JSON response
            $transactionsData = array_map(
                function (Transaction $transaction): array {
                    // Get account info
                    $account = $this->bankAccountRepository->findById(
                        BankAccountId::fromString($transaction->bankAccountId->getValue()),
                    );

                    return [
                        'id' => $transaction->id->getValue(),
                        'type' => $transaction->type->value,
                        'amount' => $transaction->amount->getAmount() / 100,
                        'currency' => $transaction->amount->getCurrency()->value,
                        'originalAmount' => $transaction->originalAmount->getAmount() / 100,
                        'originalCurrency' => $transaction->originalAmount->getCurrency()->value,
                        'exchangeRate' => $transaction->exchangeRate->getRate(),
                        'occurredAt' => $transaction->occurredAt->format('Y-m-d H:i:s'),
                        'accountIban' => $account?->iban->getValue() ?? 'N/A',
                        'status' => $transaction->status->value,
                    ];
                },
                $transactions,
            );

            return new JsonResponse([
                'transactions' => $transactionsData,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit),
                'customerName' => $customerName,
                'accountIban' => $accountIban ?? null,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
