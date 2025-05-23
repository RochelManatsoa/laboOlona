<?php

namespace App\Controller\Dashboard\Referrer;

use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
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
    public function view(JobListing $annonce): Response
    {
        if($annonce->getStatus() === JobListing::STATUS_PUBLISHED){
            return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
        }

        return $this->redirectToRoute('app_v2_job_offer');
    }
}
