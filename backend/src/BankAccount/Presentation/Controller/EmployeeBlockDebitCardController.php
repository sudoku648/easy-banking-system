<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Controller;

use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Presentation\Dto\BlockDebitCardDto;
use App\BankAccount\Presentation\Form\BlockDebitCardFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/debit-card/block', name: 'employee_block_debit_card')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeBlockDebitCardController extends AbstractController
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

        $debitCardChoices = [];
        foreach ($bankAccounts as $account) {
            /** @var array<DebitCard> $cards */
            $cards = $this->handle(
                new GetDebitCardsByBankAccountIdQuery($account['id']),
            );

            foreach ($cards as $card) {
                if (!$card->isActive) {
                    continue;
                }

                $label = sprintf(
                    '%s - %s',
                    $card->cardNumber->getValue(),
                    $account['iban'],
                );
                $debitCardChoices[$label] = $card->id->getValue();
            }
        }

        $form = $this->createForm(BlockDebitCardFormType::class, null, [
            'debit_cards' => $debitCardChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var BlockDebitCardDto $dto */
            $dto = $form->getData();

            try {
                $this->handle(
                    new BlockDebitCardCommand($dto->debitCardId),
                );

                $this->addFlash('success', 'flash.debit_card.blocked');

                return $this->redirectToRoute('employee_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.debit_card.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('bank_account/employee_block_debit_card.html.twig', [
            'form' => $form,
        ]);
    }
}
