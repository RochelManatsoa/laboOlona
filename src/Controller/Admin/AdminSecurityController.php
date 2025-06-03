<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Admin\AdminLoginForm;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;

final class AdminSecurityController extends AbstractController
{
    /**
     * @var AuthenticationUtils
     */
    private $authenticationUtils;

    public function __construct(AuthenticationUtils $authenticationUtils)
    {
        $this->authenticationUtils = $authenticationUtils;
    }

    #[Route('/admin/login', name: 'admin_login')]
    public function fakeLoginRedirect(): never
    {
        throw new \LogicException('Cette route ne devrait jamais être appelée directement. Elle est interceptée par le custom authenticator.');
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logoutAction(): void
    {
        // Left empty intentionally because this will be handled by Symfony.
    }
    
    #[Route('/wl-admin/login', name: 'wl_admin_login')]
    public function wlAdminLogin(): never
    {
        throw new \LogicException('Interception par authenticator.');
    }

    #[Route('/wl-admin/logout', name: 'wl_admin_logout')]
    public function wlAdminLogout(): void
    {
        // Left empty intentionally because this will be handled by Symfony.
    }
}