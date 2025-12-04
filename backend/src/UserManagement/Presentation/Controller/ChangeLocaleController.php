<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use App\UserManagement\Application\Command\ChangeUserLocaleCommand;
use App\UserManagement\Domain\ValueObject\Locale;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[Route('/change-locale/{locale}', name: 'change_locale', requirements: ['locale' => new EnumRequirement(Locale::class)])]
final class ChangeLocaleController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $locale, Request $request): Response
    {
        $localeObject = Locale::fromString($locale);

        // Store locale in session and request
        $request->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        // If user is authenticated, also save to database
        $user = $this->getUser();
        if ($user instanceof SecurityUser) {
            $this->commandBus->dispatch(new ChangeUserLocaleCommand(
                $user->getUser()->id,
                $localeObject,
            ));
        }

        // Redirect back to the page user came from, or to home
        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, '/login')) {
            return $this->redirectToRoute('login', ['_locale' => $locale]);
        }

        return $this->redirect($referer ?: $this->generateUrl('home'));
    }
}
