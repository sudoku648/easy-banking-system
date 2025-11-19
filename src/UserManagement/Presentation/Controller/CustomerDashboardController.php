<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            new GetBankAccountsByCustomerIdQuery($user->getId()->getValue()),
        );

        $accountsData = array_map(
            fn (BankAccount $account): array => [
                'id' => $account->getId()->getValue(),
                'iban' => $account->getIban()->getValue(),
                'balance' => $account->getBalance()->getAmount() / 100,
                'currency' => $account->getBalance()->getCurrency()->value,
                'isActive' => $account->isActive(),
            ],
            $accounts,
        );

        return $this->render('user_management/customer/dashboard.html.twig', [
            'accounts' => $accountsData,
        ]);
    }
}
