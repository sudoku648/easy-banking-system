<?php

declare(strict_types=1);

namespace App\Transaction\Api\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer/transaction/history/api', name: 'api_customer_transaction_history')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerTransactionHistoryApiController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 10);

        if (!\in_array($limit, [10, 20, 50], true)) {
            $limit = 10;
        }

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
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
            fn (BankAccount $account): BankAccountId => BankAccountId::fromString($account->id->getValue()),
            $accounts,
        );

        $offset = ($page - 1) * $limit;
        $total = $this->transactionRepository->countByBankAccountIds($accountIds);
        $transactions = $this->transactionRepository->findByBankAccountIdsPaginated($accountIds, $limit, $offset);

        // Map to array for JSON response
        $transactionsData = array_map(
            function ($transaction) use ($accounts): array {
                $filteredAccounts = array_filter(
                    $accounts,
                    fn (BankAccount $acc): bool => $acc->id->getValue() === $transaction->bankAccountId->getValue(),
                );
                $account = reset($filteredAccounts);

                return [
                    'id' => $transaction->id->getValue(),
                    'type' => $transaction->type->value,
                    'amount' => $transaction->amount->getAmount() / 100,
                    'currency' => $transaction->amount->getCurrency()->value,
                    'originalAmount' => $transaction->originalAmount->getAmount() / 100,
                    'originalCurrency' => $transaction->originalAmount->getCurrency()->value,
                    'exchangeRate' => $transaction->exchangeRate->getRate(),
                    'occurredAt' => $transaction->occurredAt->format('Y-m-d H:i:s'),
                    'accountIban' => $account instanceof BankAccount ? $account->iban->getValue() : 'N/A',
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
        ]);
    }
}
