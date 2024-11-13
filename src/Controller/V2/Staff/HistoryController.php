<?php

namespace App\Controller\V2\Staff;

use App\Entity\Logs\ActivityLog;
use App\Entity\User;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/v2/staff/history')]
class HistoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}

    #[Route('/{user}', name: 'app_v2_staff_history')]
    public function index(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $page = $request->query->getInt('page', 1);

        return $this->render('v2/staff/history/index.html.twig', [
            'user_logs' => $this->em->getRepository(ActivityLog::class)->paginateActivities($page, $user),
            'profile' => $this->userService->checkUserProfile($user),
            'user' => $user,
        ]);
    }
}
