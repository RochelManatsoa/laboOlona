<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CandidatController extends AbstractController
{
    #[Route('/dashboard/moderateur/profile/candidat', name: 'app_dashboard_moderateur_profile_candidat')]
    public function index(): Response
    {
        return $this->render('dashboard/moderateur/profile/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }
}
