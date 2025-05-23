<?php

namespace App\Controller\Dashboard\Referrer;

use App\Entity\Entreprise\JobListing;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/referrer/cooptation')]
class CooptationController extends AbstractController
{    
    #[Route('/{jobId}', name: 'app_dashboard_referrer_cooptation')]
    public function index(JobListing $annonce): Response
    {
        if($annonce->getStatus() === JobListing::STATUS_PUBLISHED){
            return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
        }

        return $this->redirectToRoute('app_v2_job_offer');
    }
    
    #[Route('/{jobId}/references', name: 'app_dashboard_referrer_cooptation_references')]
    public function references(JobListing $annonce): Response
    {
        if($annonce->getStatus() === JobListing::STATUS_PUBLISHED){
            return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
        }

        return $this->redirectToRoute('app_v2_job_offer');
    }
}
