<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
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

#[Route('/employee/transaction/deposit', name: 'employee_transaction_deposit')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDepositMoneyController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
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

                if (null === $account) {
                    throw BankAccountNotFoundException::withId($dto->bankAccountId);
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
}
