<?php

namespace App\Controller\Dashboard;

use App\Entity\CandidateProfile;
use App\Entity\Finance\Simulateur;
use App\Entity\Entreprise\JobListing;
use App\Entity\Moderateur\Assignation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/entreprise')]
class EntrepriseController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_entreprise')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_dashboard');
    }

    #[Route('/annonces', name: 'app_dashboard_entreprise_annonces')]
    public function annonces(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_job_listing');
    }

    #[Route('/annonce/new', name: 'app_dashboard_entreprise_new_annonce')]
    public function newAnnonce(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_create_job_listing');
    }

    #[Route('/annonce/{id}/edit', name: 'app_dashboard_entreprise_edit_annonce')]
    public function editAnnonce(JobListing $jobListing): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_job_listing_edit', ['jobListing' => $jobListing->getId()]);
    }

    #[Route('/annonce/{id}/delete', name: 'app_dashboard_entreprise_delete_annonce')]
    public function deleteAnnonce(JobListing $jobListing): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_job_listing_view', ['jobListing' => $jobListing->getId()]);
    }

    #[Route('/annonce/{id}/view', name: 'app_dashboard_entreprise_view_annonce')]
    public function viewAnnonce(JobListing $jobListing): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_job_listing_view', ['jobListing' => $jobListing->getId()]);
    }

    #[Route('/recherche-candidats', name: 'app_dashboard_entreprise_recherche_candidats')]
    public function rechercheCandidats(): Response
    {
        return $this->redirectToRoute('app_v2_profiles');
    }

    #[Route('/details-candidat/{id}', name: 'app_dashboard_entreprise_details_candidat')]
    public function detailsCandidat(CandidateProfile $candidateProfile): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_view_profile', ['id' => $candidateProfile->getId()]);
    }

    #[Route('/simulation-candidat/{id}', name: 'app_dashboard_entreprise_simulation_candidat')]
    public function simulationCandidat(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator');
    }

    #[Route('/rendez-vous', name: 'app_dashboard_entreprise_rendez_vous')]
    public function rendezVous(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_dashboard');
    }

    #[Route('/notifications', name: 'app_dashboard_entreprise_notifications')]
    public function notifications(): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_notification');
    }

    #[Route('/profil', name: 'app_dashboard_entreprise_profil')]
    public function profil(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_dashboard');
    }

    #[Route('/simulateur', name: 'app_dashboard_entreprise_simulateur')]
    public function simulateur(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator_create');
    }

    #[Route('/simulations', name: 'app_dashboard_entreprise_simulations')]
    public function simulations(): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator');
    }

    #[Route('/simulation/view/{id}', name: 'app_dashboard_entreprise_simulation_view')]
    public function viewSimulateur(Simulateur $simulateur): Response
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator_view', ['id' => $simulateur->getId()]);
    }

    #[Route('/service', name: 'app_dashboard_entreprise_service')]
    public function service(): Response
    {        
        return $this->redirectToRoute('app_v2_recruiter_dashboard');
    }

    #[Route('/contact/profil/{id}', name: 'app_dashboard_entreprise_contact_profile')]
    public function contactProfile(CandidateProfile $candidateProfile): Response
    {     
        return $this->redirectToRoute('app_v2_recruiter_view_profile', ['id' => $candidateProfile->getId()]);   
    }

    #[Route('/accept/profil/{id}', name: 'app_dashboard_entreprise_accept_profile')]
    public function acceptProfile(Assignation $assignation): Response
    {       
        return $this->redirectToRoute('app_v2_recruiter_dashboard'); 
    }
}
