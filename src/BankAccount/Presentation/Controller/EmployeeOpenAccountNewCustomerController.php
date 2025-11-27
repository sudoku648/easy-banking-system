<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Presentation\Dto\OpenAccountNewCustomerDto;
use App\BankAccount\Presentation\Form\OpenAccountNewCustomerFormType;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\Username;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/bank-account/open/new-customer', name: 'bank_account_open_new_customer')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeOpenAccountNewCustomerController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
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
                    Username::fromString($dto->username),
                );

                if ($customer === null) {
                    throw new \RuntimeException('Customer not found after creation');
                }

                // Open bank account
                $this->handle(
                    new OpenBankAccountCommand(
                        $customer->id->getValue(),
                        $dto->currency,
                    ),
                );

                $this->addFlash('success', 'flash.bank_account.opened_new_customer');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.bank_account.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('bank_account/open_new_customer.html.twig', [
            'form' => $form,
        ]);
    }
}
