<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Presentation\Dto\DepositMoneyDto;
use App\Transaction\Presentation\Form\DepositMoneyFormType;
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
                    new BankAccountId($dto->bankAccountId),
                );

                if ($account === null) {
                    throw new \DomainException('Bank account not found');
                }

                $this->handle(
                    new DepositMoneyCommand(
                        $dto->bankAccountId,
                        $amountInCents,
                        $account->getBalance()->getCurrency()->value,
                    ),
                );

                $this->addFlash('success', 'Cash deposited successfully!');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('transaction/deposit.html.twig', [
            'form' => $form,
        ]);
    }
}
