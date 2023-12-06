<?php

namespace App\Controller\Ajax;

use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use App\Repository\Candidate\CompetencesRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\Candidate\ExperiencesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ExperiencesRepository $experiencesRepository,
        private CompetencesRepository $competencesRepository,
    ) {
    }
    
    #[Route('/ajax/candidat', name: 'app_ajax_candidat')]
    public function index(
        Request $request,
        CandidateProfileRepository $candidateProfileRepository
    ): Response
    {
        $offset = $request->query->get('offset', 0);

        return $this->json([
            'html' => $this->renderView('components/scroll_candidat_component.html.twig', [
                'candidats' => $candidateProfileRepository->findTopExperts('', 10, $offset),
            ], []),
        ], 200, [], []);

    }

    #[Route('/ajax/ai-tools', name: 'app_ajax_aitools')]
    public function aiTools(
        Request $request,
        AffiliateToolRepository $affiliateToolRepository
    ): Response
    {
        $offset = $request->query->get('offset', 0);
        // $offset = $request->query->get('offset', 0);

        return $this->json([
            'html' => $this->renderView('ai_tool/_tools.html.twig', [
                'aiTools' => $affiliateToolRepository->findSearch('publish', 12, $offset),
            ], []),
        ], 200, [], []);

    }
    
    #[Route('/ajax/experience/edit', name: 'app_ajax_edit_experience')]
    public function editExperience(
        Request $request,
        ExperiencesRepository $experiencesRepository // Injectez le repository des expériences
    ): JsonResponse
    {
        $success = false;
        $experienceId = $request->request->get('experience_id');
        $experience = $experiencesRepository->find($experienceId);

        if ($experience) {

            $formHtml = $this->renderView('ajax/form/form_experience.html.twig', [
                'experience' => $experience,
            ]);

            $success = true;

            return $this->json([
                'success' => true,
                'form' => $formHtml,
            ]);
        } else {
            // Gérer le cas où l'expérience n'est pas trouvée
            return $this->json([
                'experience_id' => $experienceId,
                'success' => false,
                'error' => 'Expérience non trouvée',
            ]);
        }
    }

    #[Route('/ajax/experience/update/{id}', name: 'app_ajax_update_experience')]
    public function updateExperience(Request $request, ExperiencesRepository $experiencesRepository, $id): Response
    {
        $experience = $experiencesRepository->find($id);

        if (!$experience) {
            return $this->json([
                'success' => false,
                'error' => 'Expérience non trouvée',
            ]);
        }

        // Mettez à jour l'entité Experience avec les nouvelles données
        $experience->setNom($request->request->get('nom'));
        $experience->setEntreprise($request->request->get('entreprise'));
        $experience->setEnPoste(null !== $request->request->get('enPoste') ? true : false);
        $experience->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        $experience->setDateFin(new \DateTime($request->request->get('dateFin')));
        $experience->setDescription($request->request->get('description'));

        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($experience);
        $this->em->flush();

        return $this->redirectToRoute('app_dashboard_candidat_compte',[
            'success' => true,
        ]);
    }



    #[Route('/ajax/experience/delete', name: 'app_ajax_delete_experience')]
    public function deleteExperience(
        Request $request,
    ): Response
    {
        $success = false;
        $experienceId = $request->request->get('experience_id');
        $experience = $this->experiencesRepository->find($experienceId);
        if($experience instanceof Experiences){
            $this->em->remove($experience);
            $this->em->flush();
            $success = true;
        }

        return $this->json([
            'experience_id' => $experienceId,
            'success' => $success,
        ], 200, [], []);

    }
    
    #[Route('/ajax/competence/edit', name: 'app_ajax_edit_competence')]
    public function editCompetence(
        Request $request,
        CompetencesRepository $competencesRepository // Injectez le repository des expériences
    ): JsonResponse
    {
        $success = false;
        $competenceId = $request->request->get('competence_id');
        $competence = $competencesRepository->find($competenceId);

        if ($competence) {

            $formHtml = $this->renderView('ajax/form/form_competence.html.twig', [
                'competence' => $competence,
            ]);

            $success = true;

            return $this->json([
                'success' => true,
                'form' => $formHtml,
            ]);
        } else {
            // Gérer le cas où l'expérience n'est pas trouvée
            return $this->json([
                'competence_id' => $competenceId,
                'success' => false,
                'error' => 'Expérience non trouvée',
            ]);
        }
    }

    #[Route('/ajax/competence/update/{id}', name: 'app_ajax_update_competence')]
    public function updateCompetence(Request $request, CompetencesRepository $competencesRepository, $id): Response
    {
        $competence = $competencesRepository->find($id);

        if (!$competence) {
            return $this->json([
                'success' => false,
                'error' => 'Expérience non trouvée',
            ]);
        }

        // Mettez à jour l'entité Competencec avec les nouvelles données
        $competence->setNom($request->request->get('nom'));
        $competence->setNote($request->request->get('note'));
        $competence->setDescription($request->request->get('description'));

        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($competence);
        $this->em->flush();

        return $this->redirectToRoute('app_dashboard_candidat_compte',[
            'success' => true,
        ]);
    }



    #[Route('/ajax/competence/delete', name: 'app_ajax_delete_competence')]
    public function deleteCompetence(
        Request $request,
    ): Response
    {
        $success = false;
        $competenceId = $request->request->get('competence_id');
        $competence = $this->competencesRepository->find($competenceId);
        if($competence instanceof Competences){
            $this->em->remove($competence);
            $this->em->flush();
            $success = true;
        }

        return $this->json([
            'competence_id' => $competenceId,
            'success' => $success,
        ], 200, [], []);

    }

}
