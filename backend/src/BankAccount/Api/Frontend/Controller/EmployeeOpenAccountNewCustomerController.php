<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\OpenAccountNewCustomerDto;
use App\BankAccount\Api\Frontend\Dto\Part\AddressDto;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeOpenAccountNewCustomerController extends AbstractController
{
    use HandleTrait;

    #[Route('/open-account-new-customer', name: 'api_employee_open_account_new', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        OpenAccountNewCustomerDto $dto,
    ): JsonResponse {
        try {
            // Create customer
            /** @var string $customerId */
            $customerId = $this->handle(
                new CreateCustomerCommand(
                    username: $dto->username,
                    password: $dto->password,
                    firstName: $dto->firstName,
                    lastName: $dto->lastName,
                    permanentResidenceStreet: $dto->permanentResidence->street,
                    permanentResidenceCity: $dto->permanentResidence->city,
                    permanentResidencePostalCode: $dto->permanentResidence->postalCode,
                    permanentResidenceCountry: $dto->permanentResidence->country,
                    correspondenceAddresses: array_map(
                        fn (AddressDto $address): array => [
                            'street' => $address->street,
                            'city' => $address->city,
                            'postalCode' => $address->postalCode,
                            'country' => $address->country,
                        ],
                        $dto->correspondenceAddresses,
                    ),
                ),
            );

            // Open account for the new customer
            /** @var string $accountId */
            $accountId = $this->handle(
                new OpenBankAccountCommand(
                    $customerId,
                    $dto->currency,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Customer created and account opened successfully',
                data: [
                    'customerId' => $customerId,
                    'accountId' => $accountId,
                ],
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
