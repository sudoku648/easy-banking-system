<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Presentation\Dto\CloseBankAccountDto;
use App\BankAccount\Presentation\Dto\OpenAccountExistingCustomerDto;
use App\BankAccount\Presentation\Dto\OpenAccountNewCustomerDto;
use App\BankAccount\Presentation\Form\CloseBankAccountFormType;
use App\BankAccount\Presentation\Form\OpenAccountExistingCustomerFormType;
use App\BankAccount\Presentation\Form\OpenAccountNewCustomerFormType;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\Username;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/bank-account')]
#[IsGranted('ROLE_EMPLOYEE')]
final class BankAccountController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/open/new-customer', name: 'bank_account_open_new_customer')]
    public function openNewCustomer(Request $request): Response
    {
        $form = $this->createForm(OpenAccountNewCustomerFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var OpenAccountNewCustomerDto $dto */
            $dto = $form->getData();

            try {
                // Create customer first
                $this->handle(
                    new CreateCustomerCommand(
                        $dto->username,
                        $dto->password,
                        $dto->firstName,
                        $dto->lastName,
                    ),
                );

                // Get the newly created customer ID
                $customer = $this->userRepository->findByUsername(
                    new Username($dto->username),
                );

                if ($customer === null) {
                    throw new \RuntimeException('Customer not found after creation');
                }

                // Open bank account
                $this->handle(
                    new OpenBankAccountCommand(
                        $customer->getId()->getValue(),
                        $dto->currency,
                    ),
                );

                $this->addFlash('success', 'Bank account opened successfully for new customer!');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('bank_account/open_new_customer.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/open/existing-customer', name: 'bank_account_open_existing_customer')]
    public function openExistingCustomer(Request $request): Response
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

                $this->addFlash('success', 'Bank account opened successfully for existing customer!');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('bank_account/open_existing_customer.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/close', name: 'bank_account_close')]
    public function close(Request $request): Response
    {
        /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

        $form = $this->createForm(CloseBankAccountFormType::class, null, [
            'accounts' => $accounts,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CloseBankAccountDto $dto */
            $dto = $form->getData();

            try {
                $this->handle(
                    new CloseBankAccountCommand(
                        $dto->bankAccountId,
                    ),
                );

                $this->addFlash('success', 'Bank account closed successfully!');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('bank_account/close.html.twig', [
            'form' => $form,
        ]);
    }
}
