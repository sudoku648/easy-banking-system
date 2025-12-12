<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\CustomerBlockDebitCardDto;
use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerBlockDebitCardController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/block-debit-card', name: 'api_customer_block_card', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        CustomerBlockDebitCardDto $dto,
        DebitCardRepositoryInterface $debitCardRepository,
    ): JsonResponse {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Get the debit card
            $card = $debitCardRepository->findById(DebitCardId::fromString($dto->cardId));

            if (null === $card) {
                return new UnprocessableEntityResponse(
                    message: 'Debit card not found',
                )->toJsonResponse();
            }

            // Verify card belongs to one of user's accounts
            /** @var array<BankAccount> $bankAccounts */
            $bankAccounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $bankAccounts);
            if (!\in_array($card->bankAccountId->getValue(), $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Debit card does not belong to your accounts',
                )->toJsonResponse();
            }

            if (!$card->isActive) {
                return new UnprocessableEntityResponse(
                    message: 'Debit card is already blocked',
                )->toJsonResponse();
            }

            $this->handle(
                new BlockDebitCardCommand($dto->cardId),
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
