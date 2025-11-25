<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use App\UserManagement\Application\Command\ChangePasswordCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use App\UserManagement\Presentation\Dto\ChangePasswordDto;
use App\UserManagement\Presentation\Form\ChangePasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDashboardController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/dashboard', name: 'employee_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('user_management/employee/dashboard.html.twig');
    }

    #[Route('/change-password', name: 'employee_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        $dto = new ChangePasswordDto();
        $form = $this->createForm(ChangePasswordFormType::class, $dto);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handle(
                new ChangePasswordCommand(
                    userId: $user->id,
                    currentPassword: $dto->currentPassword,
                    newPassword: $dto->newPassword,
                ),
            );

            $this->addFlash('success', 'user.password_changed_successfully');

            return $this->redirectToRoute('employee_dashboard');
        }

        return $this->render('user_management/employee/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
