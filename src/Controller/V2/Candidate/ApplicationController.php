<?php

namespace App\Controller\V2\Candidate;

use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/v2/candidate/application')]
class ApplicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private CandidatManager $candidatManager,
        private PaginatorInterface $paginatorInterface,
    ){}

    #[Route('/', name: 'app_v2_candidate_application')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        
        return $this->render('v2/dashboard/candidate/application/index.html.twig', [
            'pendings' => $this->paginatorInterface->paginate(
                $this->candidatManager->getPendingApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'randezvous' => $this->paginatorInterface->paginate(
                $this->candidatManager->getMettingApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'accepteds' => $this->paginatorInterface->paginate(
                $this->candidatManager->getAcceptedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'refuseds' => $this->paginatorInterface->paginate(
                $this->candidatManager->getRefusedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'archiveds' => $this->paginatorInterface->paginate(
                $this->candidatManager->getArchivedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }
}
