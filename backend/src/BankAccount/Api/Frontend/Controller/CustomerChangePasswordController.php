<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Controller;

use App\BankAccount\Api\Frontend\Dto\ChangePasswordDto;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Application\Command\ChangePasswordCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/frontend/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerChangePasswordController extends AbstractController
{
    use HandleTrait;

    #[Route('/change-password', name: 'api_customer_change_password', methods: ['POST'])]
    public function __invoke(
        #[DynamicDto]
        ChangePasswordDto $dto,
    ): JsonResponse {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            $this->handle(
                new ChangePasswordCommand(
                    userId: $user->id,
                    currentPassword: $dto->currentPassword,
                    newPassword: $dto->newPassword,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Password changed successfully',
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
