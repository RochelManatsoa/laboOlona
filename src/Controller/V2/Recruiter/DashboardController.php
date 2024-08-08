<?php

namespace App\Controller\V2\Recruiter;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v2/recruiter/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_v2_recruiter_dashboard')]
    public function index(): Response
    {
        return $this->render('v2/dashboard/recruiter/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
