<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'home')]
final class HomeController extends AbstractController
{
    public function __invoke(Request $request): Response
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
}
