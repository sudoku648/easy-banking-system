<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logout', name: 'logout')]
final class LogoutController extends AbstractController
{
    public function __invoke(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
