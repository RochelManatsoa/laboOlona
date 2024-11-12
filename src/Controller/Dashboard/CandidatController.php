<?php

namespace App\Controller\Dashboard;

use App\Entity\Entreprise\JobListing;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    #[Route('/submit-availability', name: 'submit_availability')]
    public function submitAvailability(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route("/profil", name: "profil")]
    public function profil(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(): Response
    {
        return $this->redirectToRoute('app_v2_job_offer');
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_candidat_annonce_show')]
    public function showAnnonce(JobListing $annonce): Response
    {
        return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
    }

    #[Route('/annonce/{jobId}/details', name: 'app_dashboard_candidat_annonce_details')]
    public function detailsAnnonce(JobListing $annonce): Response
    {
        return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $annonce->getId()]);
    }

    #[Route('/all/annonces', name: 'app_dashboard_candidat_annonces')]
    public function allAnnonces(): Response
    {
        return $this->redirectToRoute('app_v2_applications');
    }

    #[Route('/compte', name: 'app_dashboard_candidat_compte')]
    public function compte(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/delete/{cvId}', name: 'app_delete_cv')]
    public function deleteEditedCV(int $cvId): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/guides/astuce', name: 'app_dashboard_guides_astuce')]
    public function astuce(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/guides/lettre-de-motivation', name: 'app_dashboard_guides_motivation')]
    public function motivation(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/guides/cv', name: 'app_dashboard_guides_cv')]
    public function cv(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

    #[Route('/guides/reseautage', name: 'app_dashboard_guides_reseautage')]
    public function reseautage(): Response
    {
        return $this->redirectToRoute('app_v2_candidate_dashboard');
    }

}
