<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\ValueObject\Iban;
use App\Transaction\Application\Command\TransferMoneyCommand;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Presentation\Dto\TransferMoneyDto;
use App\Transaction\Presentation\Form\TransferMoneyFormType;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer/transaction')]
#[IsGranted('ROLE_CUSTOMER')]
final class TransactionController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/transfer', name: 'transaction_transfer')]
    public function transfer(Request $request): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->getId()->getValue()),
        );

        $activeAccounts = array_values(array_filter($accounts, fn (BankAccount $account): bool => $account->isActive()));

        $accountsData = array_map(
            fn (BankAccount $account): array => [
                'id' => $account->getId()->getValue(),
                'iban' => $account->getIban()->getValue(),
                'balance' => $account->getBalance()->getAmount() / 100,
                'currency' => $account->getBalance()->getCurrency()->value,
            ],
            $activeAccounts,
        );

        $form = $this->createForm(TransferMoneyFormType::class, null, [
            'accounts' => $accountsData,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TransferMoneyDto $dto */
            $dto = $form->getData();

            try {
                // Find the "To" account to get its ID
                $toIban = new Iban($dto->toIban);
                $toAccount = $this->bankAccountRepository->findByIban($toIban);

                if ($toAccount === null) {
                    throw new \DomainException('Destination account not found in our bank');
                }

                // Convert amount to cents
                $amountInCents = (int) round($dto->amount * 100);

                // Get source account to determine currency
                $fromAccount = $this->bankAccountRepository->findById(
                    new BankAccountId($dto->fromBankAccountId),
                );

                if ($fromAccount === null) {
                    throw new \DomainException('Source account not found');
                }

                $this->handle(
                    new TransferMoneyCommand(
                        $dto->fromBankAccountId,
                        $toAccount->getId()->getValue(),
                        $amountInCents,
                        $fromAccount->getBalance()->getCurrency()->value,
                    ),
                );

                $this->addFlash('success', 'Transfer completed successfully!');

                return $this->redirectToRoute('customer_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('transaction/transfer.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/history', name: 'transaction_history')]
    public function history(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->getId()->getValue()),
        );

        $accountIds = array_map(
            fn (BankAccount $account): \App\Transaction\Domain\ValueObject\BankAccountId => new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
            $accounts,
        );

        $transactions = $this->transactionRepository->findByBankAccountIds($accountIds);

        // Map to array for template
        $transactionsData = array_map(
            function ($transaction) use ($accounts): array {
                $filteredAccounts = array_filter(
                    $accounts,
                    fn (BankAccount $acc): bool => $acc->getId()->getValue() === $transaction->getBankAccountId()->getValue(),
                );
                $account = reset($filteredAccounts);

                return [
                    'id' => $transaction->getId()->getValue(),
                    'type' => $transaction->getType()->value,
                    'amount' => $transaction->getAmount()->getAmount() / 100,
                    'currency' => $transaction->getAmount()->getCurrency()->value,
                    'originalAmount' => $transaction->getOriginalAmount()->getAmount() / 100,
                    'originalCurrency' => $transaction->getOriginalAmount()->getCurrency()->value,
                    'exchangeRate' => $transaction->getExchangeRate()->getRate(),
                    'occurredAt' => $transaction->getOccurredAt()->format('Y-m-d H:i:s'),
                    'accountIban' => $account instanceof BankAccount ? $account->getIban()->getValue() : 'N/A',
                ];
            },
            $transactions,
        );

        return $this->render('transaction/history.html.twig', [
            'transactions' => $transactionsData,
        ]);
    }
}
