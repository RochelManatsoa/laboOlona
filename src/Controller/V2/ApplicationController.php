<?php

namespace App\Controller\V2;

use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/v2/dashboard')]
class ApplicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private CandidatManager $candidatManager,
        private PaginatorInterface $paginatorInterface,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    #[Route('/applications', name: 'app_v2_applications')]
    public function index(Request $request): Response
    {
        $profile = $this->userService->checkProfile();
        $params = [
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
        ];

        if ($profile instanceof CandidateProfile) {
            $additionalParams = [
                'pendings' => $this->paginatorInterface->paginate(
                    $this->candidatManager->getPendingApplications($profile),
                    $request->query->getInt('page', 1),
                    10
                ),
                'randezvous' => $this->paginatorInterface->paginate(
                    $this->candidatManager->getMettingApplications($profile),
                    $request->query->getInt('page', 1),
                    10
                ),
                'accepteds' => $this->paginatorInterface->paginate(
                    $this->candidatManager->getAcceptedApplications($profile),
                    $request->query->getInt('page', 1),
                    10
                ),
                'refuseds' => $this->paginatorInterface->paginate(
                    $this->candidatManager->getRefusedApplications($profile),
                    $request->query->getInt('page', 1),
                    10
                ),
                'archiveds' => $this->paginatorInterface->paginate(
                    $this->candidatManager->getArchivedApplications($profile),
                    $request->query->getInt('page', 1),
                    10
                ),
            ];
        
            $params = array_merge($params, $additionalParams);
        }
        
        return $this->render('v2/dashboard/application/index.html.twig', $params);
    }
}
