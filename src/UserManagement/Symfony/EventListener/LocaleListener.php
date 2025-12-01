<?php

declare(strict_types=1);

namespace App\UserManagement\Symfony\EventListener;

use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 30)]
final readonly class LocaleListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Get locale from authenticated user first (highest priority)
        $token = $this->tokenStorage->getToken();

        if (null !== $token) {
            $user = $token->getUser();

            if ($user instanceof SecurityUser) {
                $locale = $user->getUser()->locale->value;
                $request->setLocale($locale);
                $request->attributes->set('_locale', $locale);
                $request->getSession()->set('_locale', $locale);

                return;
            }
        }

        // For non-authenticated users: check if locale is in the URL route parameters
        if ($request->attributes->has('_locale')) {
            /** @var string $locale */
            $locale = $request->attributes->get('_locale');
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);

            return;
        }

        // Fall back to session locale
        if ($request->getSession()->has('_locale')) {
            /** @var string $locale */
            $locale = $request->getSession()->get('_locale');
            $request->setLocale($locale);
            $request->attributes->set('_locale', $locale);
        }
    }
}
