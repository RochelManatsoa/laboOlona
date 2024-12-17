<?php

namespace App\Controller;

use App\Service\Annonce\AnnonceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function __construct(private AnnonceService $annonceService)
    {}
    
    #[Route('/v1', name: 'app_olona_talents')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/contact', name: 'app_home_contact')]
    public function contact(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/service', name: 'app_home_service')]
    public function service(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/legal-mentions', name: 'app_home_legal')]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig', []);
    }

    #[Route('/privacy-policy', name: 'app_home_privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig', []);
    }

    #[Route('/terms-and-conditions', name: 'app_home_terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig', []);
    }

    #[Route('/formulaire', name: 'app_home_form', methods: ['POST'])]
    public function form(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $annonceId = $request->request->get('annonce_id');
            $jobId = $request->request->get('job_id');
            $this->annonceService->add($annonceId);
            if (!$this->getUser()) {
                return $this->redirectToRoute('app_login');
            }
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $jobId]);
        }
    }

    #[Route('/simulateur-portage-salarial', name: 'app_home_simulateur_portage')]
    public function simulateur(): Response 
    {        
        return $this->render('home/simulateur.html.twig', []);
    }

    #[Route('/simulateur-entreprise', name: 'app_home_simulateur_entreprise')]
    public function simulateurEntreprise(): Response 
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator');
    }
}
