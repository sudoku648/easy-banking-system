<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/dashboard', name: 'employee_dashboard')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDashboardController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('user_management/employee/dashboard.html.twig');
    }
}
