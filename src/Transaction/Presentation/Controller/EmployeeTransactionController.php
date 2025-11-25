<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Presentation\Dto\DepositMoneyDto;
use App\Transaction\Presentation\Dto\SelectCustomerForHistoryDto;
use App\Transaction\Presentation\Form\DepositMoneyFormType;
use App\Transaction\Presentation\Form\SelectCustomerForHistoryFormType;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/transaction')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeTransactionController extends AbstractController
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

    #[Route('/deposit', name: 'employee_transaction_deposit')]
    public function deposit(Request $request): Response
    {
        /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

        $form = $this->createForm(DepositMoneyFormType::class, null, [
            'accounts' => $accounts,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DepositMoneyDto $dto */
            $dto = $form->getData();

            try {
                // Convert amount to cents
                $amountInCents = (int) round($dto->amount * 100);

                // Get account to determine currency
                $account = $this->bankAccountRepository->findById(
                    BankAccountId::fromString($dto->bankAccountId),
                );

                if ($account === null) {
                    throw new \DomainException('Bank account not found');
                }

                $this->handle(
                    new DepositMoneyCommand(
                        $dto->bankAccountId,
                        $amountInCents,
                        $account->balance->getCurrency()->value,
                    ),
                );

                $this->addFlash('success', 'flash.transaction.deposit_completed');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.transaction.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('transaction/deposit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/history/select', name: 'employee_transaction_history_select')]
    public function selectCustomerForHistory(Request $request): Response
    {
        /** @var array<int, array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}> $customers */
        $customers = $this->handle(new GetAllCustomersQuery());

        /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

        $form = $this->createForm(SelectCustomerForHistoryFormType::class, null, [
            'customers' => $customers,
            'accounts' => $accounts,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SelectCustomerForHistoryDto $dto */
            $dto = $form->getData();

            // Priority: if bank account is selected, use it; otherwise use customer
            if ($dto->bankAccountId !== null) {
                return $this->redirectToRoute('employee_transaction_history_view', [
                    'bankAccountId' => $dto->bankAccountId,
                ]);
            }

            if ($dto->customerId !== null) {
                return $this->redirectToRoute('employee_transaction_history_view', [
                    'customerId' => $dto->customerId,
                ]);
            }
        }

        return $this->render('transaction/select_customer_for_history.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/history/view', name: 'employee_transaction_history_view')]
    public function viewCustomerHistory(Request $request): Response
    {
        $customerId = $request->query->get('customerId');
        $bankAccountId = $request->query->get('bankAccountId');

        if ($customerId === null && $bankAccountId === null) {
            $this->addFlash('danger', 'Please select a customer or bank account');
            return $this->redirectToRoute('employee_transaction_history_select');
        }

        $transactions = [];
        $accountIban = null;

        try {
            if ($bankAccountId !== null) {
                // View specific bank account history
                $account = $this->bankAccountRepository->findById(
                    BankAccountId::fromString((string) $bankAccountId),
                );

                if ($account === null) {
                    throw new \DomainException('Bank account not found');
                }

                $accountIban = $account->iban->getValue();
                $transactions = $this->transactionRepository->findByBankAccountId(
                    \App\Transaction\Domain\ValueObject\BankAccountId::fromString((string) $bankAccountId),
                );

                // Get customer name for display
                $customer = $this->userRepository->findById(
                    UserId::fromString($account->customerId->getValue()),
                );
                $customerName = $customer !== null
                    ? $customer->getFullName() . ' (IBAN: ' . $accountIban . ')'
                    : 'Unknown Customer (IBAN: ' . $accountIban . ')';
            } elseif ($customerId !== null) {
                // View all customer's accounts history
                /** @var array<BankAccount> $accounts */
                $accounts = $this->handle(
                    new GetBankAccountsByCustomerIdQuery((string) $customerId),
                );

                if (empty($accounts)) {
                    throw new \DomainException('No accounts found for this customer');
                }

                $accountIds = array_map(
                    fn (BankAccount $account): \App\Transaction\Domain\ValueObject\BankAccountId => \App\Transaction\Domain\ValueObject\BankAccountId::fromString($account->id->getValue()),
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
                    ];
                },
                $transactions,
            );

            return $this->render('transaction/employee_customer_history.html.twig', [
                'transactions' => $transactionsData,
                'customerName' => $customerName ?? 'N/A',
                'accountIban' => $accountIban,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error: ' . $e->getMessage());
            return $this->redirectToRoute('employee_transaction_history_select');
        }
    }
}
