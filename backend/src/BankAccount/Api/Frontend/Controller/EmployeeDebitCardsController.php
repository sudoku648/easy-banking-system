<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\DebitCard;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDebitCardsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/debit-cards', name: 'api_employee_debit_cards', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
            $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

            $allCards = [];
            foreach ($accounts as $account) {
                /** @var array<DebitCard> $cards */
                $cards = $this->handle(
                    new GetDebitCardsByBankAccountIdQuery($account['id']),
                );

                foreach ($cards as $card) {
                    if (!$card->isActive) {
                        continue;
                    }

                    $allCards[] = [
                        'id' => $card->id->getValue(),
                        'cardNumber' => $card->cardNumber->getValue(),
                        'iban' => $account['iban'],
                        'label' => \sprintf('%s - %s', $card->cardNumber->getValue(), $account['iban']),
                        'isActive' => $card->isActive,
                    ];
                }
            }

            return new ApiSuccessResponse(
                message: 'Debit cards retrieved successfully',
                data: $allCards,
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
