<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\Transaction\Presentation\Dto\SelectCustomerForHistoryDto;
use App\Transaction\Presentation\Form\SelectCustomerForHistoryFormType;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/transaction/history/select', name: 'employee_transaction_history_select')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeSelectCustomerForHistoryController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
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
}
