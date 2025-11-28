<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\IssueDebitCardCommand;
use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Presentation\Dto\IssueDebitCardDto;
use App\BankAccount\Presentation\Form\IssueDebitCardFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/debit-card/issue', name: 'employee_issue_debit_card')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeIssueDebitCardController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
    {
        /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $bankAccounts */
        $bankAccounts = $this->handle(new GetAllActiveBankAccountsQuery());

        $bankAccountChoices = [];
        foreach ($bankAccounts as $account) {
            $label = sprintf(
                '%s (%s %s)',
                $account['iban'],
                number_format($account['balance'] / 100, 2),
                $account['currency'],
            );
            $bankAccountChoices[$label] = $account['id'];
        }

        $form = $this->createForm(IssueDebitCardFormType::class, null, [
            'bank_accounts' => $bankAccountChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var IssueDebitCardDto $dto */
            $dto = $form->getData();

            try {
                $this->handle(
                    new IssueDebitCardCommand($dto->bankAccountId),
                );

                $this->addFlash('success', 'flash.debit_card.issued');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.debit_card.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('bank_account/employee_issue_debit_card.html.twig', [
            'form' => $form,
        ]);
    }
}
