<?php

namespace App\Controller\V2;

use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v2/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_v2_dashboard')]
    public function index(): Response
    {
        $profile = $this->userService->checkProfile();
        if($profile instanceof EntrepriseProfile){
            return $this->redirectToRoute('app_v2_recruiter_dashboard');
        }
        if($profile instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_candidate_dashboard');
        }
        if($profile instanceof ModerateurProfile){
            return $this->redirectToRoute('app_dashboard_moderateur');
        }

        return $this->render('v2/dashboard/index.html.twig', []);
    }
}
