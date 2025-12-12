<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\TransferDto;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Provider\IbanProviderInterface;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Transaction\Application\Command\InterbankTransferCommand;
use App\Transaction\Application\Command\TransferMoneyCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerTransferController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
        private readonly IbanProviderInterface $ibanProvider,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/transfer', name: 'api_customer_transfer', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        TransferDto $dto,
    ): JsonResponse {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $accounts);
            if (!\in_array($dto->sourceAccountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Source account not found or does not belong to you',
                )->toJsonResponse();
            }

            $toIban = Iban::fromString($dto->recipientIban);
            $fromAccount = $this->bankAccountRepository->findById(
                BankAccountId::fromString($dto->sourceAccountId),
            );

            if (null === $fromAccount) {
                throw BankAccountNotFoundException::withId($dto->sourceAccountId);
            }

            // Check if this is an internal or external transfer
            if ($this->ibanProvider->isInternalIban($toIban)) {
                // Internal transfer
                $toAccount = $this->bankAccountRepository->findByIban($toIban);

                if (null === $toAccount) {
                    throw BankAccountNotFoundException::withIban($toIban->getValue());
                }

                $this->handle(
                    new TransferMoneyCommand(
                        $dto->sourceAccountId,
                        $toAccount->id->getValue(),
                        $dto->amount,
                        $fromAccount->balance->getCurrency()->value,
                    ),
                );

                return new ApiSuccessResponse(
                    message: 'Transfer completed successfully',
                )->toJsonResponse();
            } else {
                // External/interbank transfer
                $this->handle(
                    new InterbankTransferCommand(
                        $dto->sourceAccountId,
                        $toIban->getValue(),
                        $dto->amount,
                        $fromAccount->balance->getCurrency()->value,
                    ),
                );

                return new ApiSuccessResponse(
                    message: 'Interbank transfer initiated successfully',
                )->toJsonResponse();
            }
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
