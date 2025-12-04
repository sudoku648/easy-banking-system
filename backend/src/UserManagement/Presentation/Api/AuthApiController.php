<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Api;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/frontend/auth')]
final class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?SecurityUser $user): JsonResponse
    {
        if (null === $user) {
            return new UnprocessableEntityResponse(
                message: 'Invalid credentials',
            )->toJsonResponse()->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUser();
        $role = $userData->getRole()->value;

        return new ApiSuccessResponse(
            message: 'Login successful',
            data: [
                'user' => [
                    'id' => $userData->id->getValue(),
                    'username' => $userData->username->getValue(),
                    'role' => $role,
                    'firstName' => $userData->firstName->getValue(),
                    'lastName' => $userData->lastName->getValue(),
                ],
            ],
        )->toJsonResponse();
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new ApiSuccessResponse(
            message: 'Logout successful',
        )->toJsonResponse();
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?SecurityUser $user): JsonResponse
    {
        if (null === $user) {
            return new UnprocessableEntityResponse(
                message: 'Not authenticated',
            )->toJsonResponse()->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }

        $userData = $user->getUser();
        $role = $userData->getRole()->value;

        return new ApiSuccessResponse(
            message: 'User retrieved successfully',
            data: [
                'user' => [
                    'id' => $userData->id->getValue(),
                    'username' => $userData->username->getValue(),
                    'role' => $role,
                    'firstName' => $userData->firstName->getValue(),
                    'lastName' => $userData->lastName->getValue(),
                ],
            ],
        )->toJsonResponse();
    }
}
