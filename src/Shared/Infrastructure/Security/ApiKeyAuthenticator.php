<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private const string API_KEY_HEADER = 'X-API-KEY';

    public function __construct(
        private readonly string $validApiKey,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Only support requests to /api/* endpoints
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get(self::API_KEY_HEADER);

        if (null === $apiKey) {
            throw new CustomUserMessageAuthenticationException('API key is missing');
        }

        if ($apiKey !== $this->validApiKey) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        // Use a special user identifier for API key authentication
        // Provide a user loader callback that creates an in-memory user
        return new SelfValidatingPassport(
            new UserBadge('api_key_user', fn (): InMemoryUser => new InMemoryUser('api_key_user', null, ['ROLE_API'])),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $exception->getMessageKey(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
