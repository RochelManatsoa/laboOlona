<?php

namespace App\Controller\Dashboard;

use App\Entity\Entreprise\JobListing;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/referrer')]
class ReferrerController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_referrer')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/annonces', name: 'app_dashboard_referrer_annonces')]
    public function annonces(): Response
    {
        return $this->redirectToRoute('app_v2_job_offer');
    }

    #[Route('/posting/{slug}', name: 'app_dashboard_referrer_posting_view')]
    public function view(JobListing $annonce): Response
    {
        if($annonce->getStatus() === JobListing::STATUS_PUBLISHED){
            return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
        }

        return $this->redirectToRoute('app_v2_job_offer');
    }

    #[Route('/stats', name: 'app_dashboard_referrer_stats')]
    public function stats(): Response
    {
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/rewards', name: 'app_dashboard_referrer_rewards')]
    public function rewards(): Response
    {
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/references', name: 'app_dashboard_referrer_references')]
    public function references(): Response
    {
        return $this->redirectToRoute('app_connect');
    }    

    #[Route('/info', name: 'app_dashboard_referrer_info')]
    public function info(): Response
    {
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/become', name: 'app_dashboard_referrer_become')]
    public function become(): Response
    {
        return $this->redirectToRoute('app_connect');
    }
}
