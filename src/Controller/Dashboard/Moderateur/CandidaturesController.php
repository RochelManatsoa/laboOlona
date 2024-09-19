<?php

namespace App\Controller\Dashboard\Moderateur;

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
    ) {}
    
    #[Route('/candidatures', name: 'app_dashboard_moderateur_candidatures')]
    public function candidatures(
        Request $request, 
        PaginatorInterface $paginatorInterface, 
    ): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
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
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        
        return $this->render('dashboard/moderateur/candidature/view.html.twig', [
            'application' => $applications,
        ]);
    }

    #[Route('/candidature/{id}/status', name: 'app_dashboard_moderateur_candidature_status')]
    public function statusCandidature(Request $request, Applications $applications, NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
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

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_assignation');
    }
}
