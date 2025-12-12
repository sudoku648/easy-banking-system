<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\DepositDto;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Transaction\Application\Command\DepositMoneyCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDepositController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/deposit', name: 'api_employee_deposit', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        DepositDto $dto,
    ): JsonResponse {
        try {
            // Verify account exists
            $account = $this->bankAccountRepository->findById(
                BankAccountId::fromString($dto->accountId),
            );

            if (null === $account) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found',
                )->toJsonResponse();
            }

            $this->handle(
                new DepositMoneyCommand(
                    $dto->accountId,
                    $dto->amount,
                    $account->balance->getCurrency()->value,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Deposit completed successfully',
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
