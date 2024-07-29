<?php

namespace App\Controller\V2\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v2/staff/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_v2_staff_dashboard')]
    public function index(): Response
    {
        return $this->render('v2/staff/dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
