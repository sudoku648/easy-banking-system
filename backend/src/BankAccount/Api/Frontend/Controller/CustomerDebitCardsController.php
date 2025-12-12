<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Entity\DebitCard;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerDebitCardsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/debit-cards', name: 'api_customer_debit_cards', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $allCards = [];
            foreach ($accounts as $account) {
                /** @var array<DebitCard> $cards */
                $cards = $this->handle(
                    new GetDebitCardsByBankAccountIdQuery($account->id->getValue()),
                );

                foreach ($cards as $card) {
                    if (!$card->isActive) {
                        continue;
                    }

                    $allCards[] = [
                        'id' => $card->id->getValue(),
                        'cardNumber' => $card->cardNumber->getValue(),
                        'bankAccountId' => $account->id->getValue(),
                        'iban' => $account->iban->getValue(),
                        'isActive' => $card->isActive,
                        'issuedAt' => $card->issuedAt->format('Y-m-d H:i:s'),
                    ];
                }
            }

            return new ApiSuccessResponse(
                message: 'Debit cards retrieved successfully',
                data: ['debitCards' => $allCards],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
