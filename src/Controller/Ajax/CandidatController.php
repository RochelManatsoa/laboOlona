<?php

namespace App\Controller\Ajax;

use App\Entity\Candidate\Applications;
use App\Entity\Candidate\CV;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Candidate\Langages;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\LangagesRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\Candidate\CompetencesRepository;
use App\Repository\Candidate\ExperiencesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ExperiencesRepository $experiencesRepository,
        private CompetencesRepository $competencesRepository,
        private CandidatManager $candidatManager,
        private UserService $userService,
        private LangagesRepository $langagesRepository,
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
        // $this->candidatManager->sendNotificationEmail($experience->getProfil());

        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_connect');
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
    #[Route('/ajax/application/edit', name: 'app_ajax_edit_application')]
    public function editApplication(
        Request $request,
        ApplicationsRepository $applicationsRepository // Injectez le repository des expériences
    ): JsonResponse
    {
        $success = false;
        $applicationId = $request->request->get('application_id');
        $application = $applicationsRepository->find($applicationId);

        if ($application) {

            $formHtml = $this->renderView('ajax/form/form_application.html.twig', [
                'application' => $application,
            ]);

            $success = true;

            return $this->json([
                'success' => true,
                'form' => $formHtml,
            ]);
        } else {
            // Gérer le cas où l'expérience n'est pas trouvée
            return $this->json([
                'application_id' => $applicationId,
                'success' => false,
                'error' => 'Candidature non trouvée',
            ]);
        }
    }

    #[Route('/ajax/application/update/{id}', name: 'app_ajax_update_application')]
    public function updateApplication(Request $request, ApplicationsRepository $applicationsRepository, $id): Response
    {
        $application = $applicationsRepository->find($id);

        if (!$application) {
            return $this->json([
                'success' => false,
                'error' => 'Candidature non trouvée',
            ]);
        }

        // Mettez à jour l'entité Experience avec les nouvelles données
        // $experience->setNom($request->request->get('nom'));
        // $experience->setEntreprise($request->request->get('entreprise'));
        // $experience->setEnPoste(null !== $request->request->get('enPoste') ? true : false);
        // $experience->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        // $experience->setDateFin(new \DateTime($request->request->get('dateFin')));
        // $experience->setDescription($request->request->get('description'));

        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($application);
        $this->em->flush();

        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_connect');
    }



    #[Route('/ajax/application/delete', name: 'app_ajax_delete_application')]
    public function deleteApplication(
        Request $request,
    ): Response
    {
        $success = false;
        $applicationId = $request->request->get('application_id');
        $application = $this->experiencesRepository->find($applicationId);
        if($application instanceof Applications){
            $this->em->remove($application);
            $this->em->flush();
            $success = true;
        }

        return $this->json([
            'application_id' => $applicationId,
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
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($competence);
        $this->em->flush();
        // $this->candidatManager->sendNotificationEmail($candidat);

        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_connect');
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
    
    #[Route('/ajax/langue/edit', name: 'app_ajax_edit_langue')]
    public function editLangage(
        Request $request,
        LangagesRepository $langagesRepository // Injectez le repository des expériences
    ): JsonResponse
    {
        $success = false;
        $langueId = $request->request->get('langue_id');
        $langue = $langagesRepository->find($langueId);

        if ($langue) {

            $formHtml = $this->renderView('ajax/form/form_langue.html.twig', [
                'langue' => $langue,
            ]);

            $success = true;

            return $this->json([
                'success' => true,
                'form' => $formHtml,
            ]);
        } else {
            // Gérer le cas où l'expérience n'est pas trouvée
            return $this->json([
                'langage_id' => $langueId,
                'success' => false,
                'error' => 'Expérience non trouvée',
            ]);
        }
    }

    #[Route('/ajax/langue/update/{id}', name: 'app_ajax_update_langue')]
    public function updateLangage(Request $request, LangagesRepository $langagesRepository, $id): Response
    {
        $langue = $langagesRepository->find($id);
        if (!$langue) {
            return $this->json([
                'success' => false,
                'error' => 'Langue non trouvée',
            ]);
        }

        // Mettez à jour l'entité Experience avec les nouvelles données
        $langue->setNiveau($request->request->get('niveau'));

        
        // Enregistrez les modifications dans la base de données
        $this->em->persist($langue);
        $this->em->flush();
        // $this->candidatManager->sendNotificationEmail($langue->getProfile());

        return $this->redirectToRoute('app_dashboard_candidat_compte',[
            'success' => true,
        ]);
    }



    #[Route('/ajax/langue/delete', name: 'app_ajax_delete_langue')]
    public function deleteLangage(
        Request $request,
    ): Response
    {
        $success = false;
        $langueId = $request->request->get('langue_id');
        $langue = $this->langagesRepository->find($langueId);
        if($langue instanceof Langages){
            $this->em->remove($langue);
            $this->em->flush();
            $success = true;
        }

        return $this->json([
            'experience_id' => $langueId,
            'success' => $success,
        ], 200, [], []);

    }

    #[Route('/ajax-remove-experience/{experienceId}/{userId}', name: 'app_remove_experience_user')]
    public function removeExperience(int $experienceId, int $userId)
    {
        // Récupérez l'expérience à supprimer
        $experience = $this->experiencesRepository->find($experienceId);

        if (!$experience) {
            return $this->json([
                'message' => 'Expérience non trouvée',
                'success' => false,
            ], 200, [], []);
        }

        // Assurez-vous que l'expérience appartient à l'utilisateur (si nécessaire)

        // Supprimez l'expérience
        $this->em->remove($experience);
        $this->em->flush();

        return $this->json([
            'message' => 'Expérience supprimée avec succès',
            'success' => true,
        ], 200, [], []);
    }

    #[Route('/ajax-remove-competence/{competenceId}/{userId}', name: 'app_remove_competence_user')]
    public function removeCompetence(int $competenceId, int $userId)
    {
        // Récupérez l'expérience à supprimer
        $competence = $this->competencesRepository->find($competenceId);

        if (!$competence) {
            return $this->json([
                'message' => 'Compétence non trouvée',
                'success' => false,
            ], 200, [], []);
        }

        // Assurez-vous que la competence appartient à l'utilisateur (si nécessaire)

        // Supprimez la competence
        $this->em->remove($competence);
        $this->em->flush();

        return $this->json([
            'message' => 'Compétence supprimée avec succès',
            'success' => true,
        ], 200, [], []);
    }

    #[Route('/ajax-remove-language/{languageId}/{userId}', name: 'app_remove_language_user')]
    public function removeLanguage(int $languageId, int $userId)
    {
        // Récupérez l'expérience à supprimer
        $language = $this->langagesRepository->find($languageId);

        if (!$language) {
            return $this->json([
                'message' => 'Langue non trouvée',
                'success' => false,
            ], 200, [], []);
        }

        // Assurez-vous que la language appartient à l'utilisateur (si nécessaire)

        // Supprimez la language
        $this->em->remove($language);
        $this->em->flush();

        return $this->json([
            'message' => 'Langue supprimée avec succès',
            'success' => true,
        ], 200, [], []);
    }

    #[Route('/profile/cv/{id}/select', name: 'app_profile_candidate_select_CV')]
    public function candidateSelectCV(CV $cv)
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if ($cv instanceof CV) {
            $candidat->setCv($cv->getCvLink());
            $this->em->flush();
            $message = "ok";
        }else{
            $message = "error: CV not found";
        }

        return $this->json([
            'message' => $message
        ], 200);
    }
}
