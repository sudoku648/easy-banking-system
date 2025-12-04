<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Presentation\Dto\OpenAccountExistingCustomerDto;
use App\BankAccount\Presentation\Form\OpenAccountExistingCustomerFormType;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/bank-account/open/existing-customer', name: 'bank_account_open_existing_customer')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeOpenAccountExistingCustomerController extends AbstractController
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

        $form = $this->createForm(OpenAccountExistingCustomerFormType::class, null, [
            'customers' => $customers,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var OpenAccountExistingCustomerDto $dto */
            $dto = $form->getData();

            try {
                $this->handle(
                    new OpenBankAccountCommand(
                        $dto->customerId,
                        $dto->currency,
                    ),
                );

                $this->addFlash('success', 'flash.bank_account.opened_existing_customer');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.bank_account.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('bank_account/open_existing_customer.html.twig', [
            'form' => $form,
        ]);
    }
}
