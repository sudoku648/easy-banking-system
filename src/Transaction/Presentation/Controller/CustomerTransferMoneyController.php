<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\ValueObject\Iban;
use App\Transaction\Application\Command\TransferMoneyCommand;
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

#[Route('/customer/transaction/transfer', name: 'transaction_transfer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerTransferMoneyController extends AbstractController
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
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
        );

        $activeAccounts = array_values(array_filter($accounts, fn (BankAccount $account): bool => $account->isActive));

        $accountsData = array_map(
            fn (BankAccount $account): array => [
                'id' => $account->id->getValue(),
                'iban' => $account->iban->getValue(),
                'balance' => $account->balance->getAmount() / 100,
                'currency' => $account->balance->getCurrency()->value,
            ],
            $activeAccounts,
        );

        // Pre-populate DTO with fromAccountId if provided in URL
        $dto = new TransferMoneyDto();
        $fromAccountId = $request->query->get('fromAccountId');
        if ($fromAccountId !== null) {
            $dto->fromBankAccountId = (string) $fromAccountId;
        }

        $form = $this->createForm(TransferMoneyFormType::class, $dto, [
            'accounts' => $accountsData,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TransferMoneyDto $dto */
            $dto = $form->getData();

            try {
                // Find the "To" account to get its ID
                $toIban = Iban::fromString($dto->toIban);
                $toAccount = $this->bankAccountRepository->findByIban($toIban);

                if ($toAccount === null) {
                    throw new \DomainException('Destination account not found in our bank');
                }

                // Convert amount to cents
                $amountInCents = (int) round($dto->amount * 100);

                // Get source account to determine currency
                $fromAccount = $this->bankAccountRepository->findById(
                    BankAccountId::fromString($dto->fromBankAccountId),
                );

                if ($fromAccount === null) {
                    throw new \DomainException('Source account not found');
                }

                $this->handle(
                    new TransferMoneyCommand(
                        $dto->fromBankAccountId,
                        $toAccount->id->getValue(),
                        $amountInCents,
                        $fromAccount->balance->getCurrency()->value,
                    ),
                );

                $this->addFlash('success', 'flash.transaction.transfer_completed');

                return $this->redirectToRoute('customer_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('danger', json_encode(['key' => 'flash.transaction.error', 'parameters' => ['error' => $e->getMessage()]], JSON_THROW_ON_ERROR));
            }
        }

        return $this->render('transaction/transfer.html.twig', [
            'form' => $form,
        ]);
    }
}
