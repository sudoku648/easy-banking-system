<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\OpenAccountExistingCustomerDto;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeOpenAccountExistingCustomerController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/open-account-existing-customer', name: 'api_employee_open_account_existing', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        OpenAccountExistingCustomerDto $dto,
    ): JsonResponse {
        try {
            // Verify customer exists
            $customer = $this->userRepository->findById(UserId::fromString($dto->customerId));
            if (null === $customer) {
                return new UnprocessableEntityResponse(
                    message: 'Customer not found',
                )->toJsonResponse();
            }

            /** @var string $accountId */
            $accountId = $this->handle(
                new OpenBankAccountCommand(
                    $dto->customerId,
                    $dto->currency,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Account opened successfully',
                data: ['accountId' => $accountId],
                statusCode: Response::HTTP_CREATED,
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
