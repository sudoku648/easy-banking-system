<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class LocaleAwareAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        // Get locale from session or request
        $locale = $request->getSession()->get('_locale', $request->getLocale());

        // Store the error in session for display
        $request->getSession()->set('_security.last_error', $exception);

        // Redirect to localized login page
        $targetUrl = $this->urlGenerator->generate('login', ['_locale' => $locale]);

        return new RedirectResponse($targetUrl);
    }
}
