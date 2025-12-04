<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Presentation\Dto\CloseBankAccountDto;
use App\BankAccount\Presentation\Form\CloseBankAccountFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/bank-account/close', name: 'bank_account_close')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeCloseBankAccountController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
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

                $this->addFlash('success', 'flash.bank_account.closed');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.bank_account.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('bank_account/close.html.twig', [
            'form' => $form,
        ]);
    }
}
