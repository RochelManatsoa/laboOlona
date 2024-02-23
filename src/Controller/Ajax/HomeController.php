<?php

namespace App\Controller\Ajax;

use App\Twig\AppExtension;
use App\Manager\NotificationManager;
use App\Manager\ModerateurManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Finance\DeviseRepository;
use App\Repository\Moderateur\AssignationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobListingRepository $jobListingRepository,
        private AssignationRepository $assignationRepository,
        private NotificationManager $notificationManager,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private AppExtension $appExtension,
        private RequestStack $requestStack,
        private DeviseRepository $deviseRepository
    )
    {}

    #[Route('/ajax/home/simulateur', name: 'ajax_home_simulateur')]
    public function simulateur(Request $request): Response
    {
        $simulateur = $request->request->all('simulateur');
        $session = $this->requestStack->getSession();
        $session->set('simulateur', [ 'simu' => $simulateur]);

        return $this->json([
            'message' => 'Session mise à jour',
            'simulateur' => $simulateur,
        ], 200, []);
    }

    #[Route('/ajax/get/simulateur/{id}', name: 'ajax_home_simulateurs')]
    public function simulateurs(Request $request, int $id): Response
    {
        return $this->json([
            'devise' => $this->deviseRepository->find($id),
        ], 200, [], ['groups' => 'devise']);
    }

}
