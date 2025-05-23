<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Logs\ActivityLog;
use App\Service\ActivityLogger;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/activity/log')]
class ActivityLogController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private UserService $userService,
        private ActivityLogger $activityLogger,
    ){}

    #[Route('/', name: 'app_v2_activity_log')]
    public function index(): Response
    {
        return $this->render('v2/activity_log/index.html.twig', []);
    }

    #[Route('/click', name: 'app_v2_activity_log_click', methods:['POST'])]
    public function click(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $pageUrl = $request->request->get('page', '');
        
        if ($currentUser) {
            $this->activityLogger->logPageViewActivity($currentUser, $pageUrl);
            
            return $this->json([
                'message' => 'Logged successfully',
            ], Response::HTTP_ACCEPTED);
        }
    
        return $this->json([
            'message' => 'Not logged',
        ], Response::HTTP_ACCEPTED);
    }
}
