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
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(Request $request): Response
    {
        if ($this->isGranted('ROLE_EMPLOYEE')) {
            return $this->redirectToRoute('employee_dashboard');
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            return $this->redirectToRoute('customer_dashboard');
        }

        $locale = $request->getSession()->get('_locale', $request->getLocale());

        return $this->redirectToRoute('login', ['_locale' => $locale]);
    }

    #[Route('/login', name: 'login_redirect')]
    public function loginRedirect(Request $request): Response
    {
        $locale = $request->getSession()->get('_locale', $request->getLocale());

        return $this->redirectToRoute('login', ['_locale' => $locale]);
    }

    #[Route('/{_locale}/login', name: 'login', requirements: ['_locale' => new EnumRequirement(Locale::class)])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user_management/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/change-locale/{locale}', name: 'change_locale', requirements: ['locale' => new EnumRequirement(Locale::class)])]
    public function changeLocale(string $locale, Request $request, MessageBusInterface $commandBus): Response
    {
        $localeObject = Locale::fromString($locale);

        // Store locale in session and request
        $request->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        // If user is authenticated, also save to database
        $user = $this->getUser();
        if ($user instanceof SecurityUser) {
            $commandBus->dispatch(new ChangeUserLocaleCommand(
                $user->getUser()->getId(),
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
