<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\UserManagement\Application\Command\ChangePasswordCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use App\UserManagement\Presentation\Dto\ChangePasswordDto;
use App\UserManagement\Presentation\Form\ChangePasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerDashboardController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/dashboard', name: 'customer_dashboard')]
    public function dashboard(): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
        );

        $accountsData = array_map(
            fn (BankAccount $account): array => [
                'id' => $account->id->getValue(),
                'iban' => $account->iban->getValue(),
                'balance' => $account->balance->getAmount() / 100,
                'currency' => $account->balance->getCurrency()->value,
                'isActive' => $account->isActive,
            ],
            $accounts,
        );

        return $this->render('user_management/customer/dashboard.html.twig', [
            'accounts' => $accountsData,
        ]);
    }

    #[Route('/change-password', name: 'customer_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        $dto = new ChangePasswordDto();
        $form = $this->createForm(ChangePasswordFormType::class, $dto);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handle(
                new ChangePasswordCommand(
                    userId: $user->id,
                    currentPassword: $dto->currentPassword,
                    newPassword: $dto->newPassword,
                ),
            );

            $this->addFlash('success', 'user.password_changed_successfully');

            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->render('user_management/customer/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
