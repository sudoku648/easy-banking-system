<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class RoleBasedAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $roles = $token->getRoleNames();

        if (\in_array('ROLE_EMPLOYEE', $roles, true)) {
            $targetUrl = $this->urlGenerator->generate('employee_dashboard');
        } elseif (\in_array('ROLE_CUSTOMER', $roles, true)) {
            $targetUrl = $this->urlGenerator->generate('customer_dashboard');
        } else {
            $targetUrl = $this->urlGenerator->generate('home');
        }

        return new RedirectResponse($targetUrl);
    }
}
