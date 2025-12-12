<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\CustomerBlockDebitCardDto;
use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Entity\DebitCard;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerBlockDebitCardController extends AbstractController
{
    use HandleTrait;

    #[Route('/block-debit-card', name: 'api_customer_block_card', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        CustomerBlockDebitCardDto $dto,
    ): JsonResponse {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $bankAccounts */
            $bankAccounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $bankAccounts);
            if (!\in_array($dto->accountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found or does not belong to you',
                )->toJsonResponse();
            }

            // Get active debit cards for this account
            /** @var array<DebitCard> $cards */
            $cards = $this->handle(
                new GetDebitCardsByBankAccountIdQuery($dto->accountId),
            );

            $activeCard = null;
            foreach ($cards as $card) {
                if ($card->isActive) {
                    $activeCard = $card;
                    break;
                }
            }

            if (null === $activeCard) {
                return new UnprocessableEntityResponse(
                    message: 'No active debit card found for this account',
                )->toJsonResponse();
            }

            $this->handle(
                new BlockDebitCardCommand($activeCard->id->getValue()),
            );

            return new ApiSuccessResponse(
                message: 'Debit card blocked successfully',
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
