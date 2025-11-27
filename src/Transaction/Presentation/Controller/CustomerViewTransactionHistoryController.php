<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer/transaction/history', name: 'transaction_history')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerViewTransactionHistoryController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
        );

        $accountIds = array_map(
            fn (BankAccount $account): BankAccountId => BankAccountId::fromString($account->id->getValue()),
            $accounts,
        );

        $transactions = $this->transactionRepository->findByBankAccountIds($accountIds);

        // Map to array for template
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
                ];
            },
            $transactions,
        );

        return $this->render('transaction/history.html.twig', [
            'transactions' => $transactionsData,
        ]);
    }
}
