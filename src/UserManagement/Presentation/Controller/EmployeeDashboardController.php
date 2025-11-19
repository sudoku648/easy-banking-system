<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'employee_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('user_management/employee/dashboard.html.twig');
    }
}
