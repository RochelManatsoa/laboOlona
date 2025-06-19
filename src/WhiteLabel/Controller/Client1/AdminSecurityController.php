<?php

namespace App\WhiteLabel\Controller\Client1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

final class AdminSecurityController extends AbstractController
{
    #[Route('/wl-admin/login', name: 'wl_admin_login')]
    public function login(): never
    {
        throw new \LogicException('Interception par authenticator.');
    }

    #[Route('/wl-admin/logout', name: 'wl_admin_logout')]
    public function logout(): void
    {
        // Left empty intentionally because this will be handled by Symfony.
    }
}