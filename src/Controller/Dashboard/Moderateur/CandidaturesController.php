<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\Candidate\ApplicationsRepository;
use App\Form\Search\Candidature\ModerateurCandidatureSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class CandidaturesController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
    ) {
    }

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_connect');
        }

        return null;
    }
    
    #[Route('/candidatures', name: 'app_dashboard_moderateur_candidatures')]
    public function candidatures(
        Request $request, 
        PaginatorInterface $paginatorInterface, 
    ): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
        $status = $request->query->get('status');
        $form = $this->createForm(ModerateurCandidatureSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllCandidatures(null, null, null, $status);
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $entreprise = $form->get('entreprise')->getData();
            $candidat = $form->get('candidat')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->findAllCandidatures($titre, $entreprise, $candidat, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/candidature/_candidatures.html.twig', [
                        'candidatures' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }
        
        return $this->render('dashboard/moderateur/candidature/index.html.twig', [
            'candidatures' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/candidature/{id}', name: 'app_dashboard_moderateur_candidature_view')]
    public function candidature(Request $request, Applications $applications): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/moderateur/candidature/view.html.twig', [
            'application' => $applications,
        ]);
    }

    #[Route('/candidature/{id}/status', name: 'app_dashboard_moderateur_candidature_status')]
    public function statusCandidature(Request $request, Applications $applications, NotificationRepository $notificationRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, Applications::getArrayStatuses())) {
            $applications->setStatus($status);
            $this->em->persist($applications);
            $this->em->flush();
            /** Envoi mail */

            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidat_applications', ['id' => $applications->getCandidat()->getId()]);
        
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }
}
