<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Transaction\Domain\Exception\NoAccountsFoundException;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId as BankAccountIdVO;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/transaction/history/view', name: 'employee_transaction_history_view')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeViewCustomerHistoryController extends AbstractController
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

    public function __invoke(Request $request): Response
    {
        $customerId = $request->query->get('customerId');
        $bankAccountId = $request->query->get('bankAccountId');

        if (null === $customerId && null === $bankAccountId) {
            $this->addFlash('danger', 'Please select a customer or bank account');
            return $this->redirectToRoute('employee_transaction_history_select');
        }

        $transactions = [];
        $accountIban = null;

        try {
            if (null !== $bankAccountId) {
                // View specific bank account history
                $account = $this->bankAccountRepository->findById(
                    BankAccountId::fromString((string) $bankAccountId),
                );

                if (null === $account) {
                    throw BankAccountNotFoundException::withId((string) $bankAccountId);
                }

                $accountIban = $account->iban->getValue();
                $transactions = $this->transactionRepository->findByBankAccountId(
                    BankAccountIdVO::fromString((string) $bankAccountId),
                );

                // Get customer name for display
                $customer = $this->userRepository->findById(
                    UserId::fromString($account->customerId->getValue()),
                );
                $customerName = null !== $customer
                    ? $customer->getFullName() . ' (IBAN: ' . $accountIban . ')'
                    : 'Unknown Customer (IBAN: ' . $accountIban . ')';
            } elseif (null !== $customerId) {
                // View all customer's accounts history
                /** @var array<BankAccount> $accounts */
                $accounts = $this->handle(
                    new GetBankAccountsByCustomerIdQuery((string) $customerId),
                );

                if (empty($accounts)) {
                    throw NoAccountsFoundException::generic();
                }

                $accountIds = array_map(
                    fn (BankAccount $account): BankAccountIdVO => BankAccountIdVO::fromString($account->id->getValue()),
                    $accounts,
                );

                $transactions = $this->transactionRepository->findByBankAccountIds($accountIds);

                // Get customer name
                $customer = $this->userRepository->findById(
                    UserId::fromString((string) $customerId),
                );
                $customerName = $customer?->getFullName() ?? 'Unknown Customer';
            }

            // Map to array for template
            $transactionsData = array_map(
                function ($transaction): array {
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

            return $this->render('transaction/employee_customer_history.html.twig', [
                'transactions' => $transactionsData,
                'customerName' => $customerName,
                'accountIban' => $accountIban,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error: ' . $e->getMessage());
            return $this->redirectToRoute('employee_transaction_history_select');
        }
    }
}
