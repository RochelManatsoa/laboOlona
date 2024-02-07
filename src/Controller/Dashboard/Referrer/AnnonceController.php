<?php

namespace App\Controller\Dashboard\Referrer;

use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/referrer/annonce')]
class AnnonceController extends AbstractController
{
    public function __construct(
        private UserService $userService,
    ) {}
    
    #[Route('/{jobId}', name: 'app_dashboard_referrer_annonce_view')]
    public function view(Request $request, JobListing $annonce): Response
    {
        return $this->render('dashboard/referrer/annonce/view.html.twig', [
            'annonce' => $annonce,
        ]);
    }
}
