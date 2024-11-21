<?php

namespace App\Controller\V2\Staff;

use App\Entity\User;
use App\Data\UserData;
use App\Entity\Logs\ActivityLog;
use App\Form\Search\UserDataType;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/staff/history')]
class HistoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_v2_staff_history')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $page = $request->query->get('page', 1);
        $array = [
            'startDate' => $request->query->get('startDate', ""),
            'endDate' => $request->query->get('endDate', ""),
            'page' => is_numeric($page) && (int)$page > 0 ? (int)$page : 1,
            'days' => $request->query->getInt('days', 1),
        ];

        return $this->render('v2/staff/history/index.html.twig', [
            'users' => $this->em->getRepository(User::class)->paginateUsers($array),
            'query' => $array,
        ]);
    }

    #[Route('/{user}', name: 'app_v2_staff_history_user')]
    public function user(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $page = $request->query->getInt('page', 1);

        return $this->render('v2/staff/history/view.html.twig', [
            'user_logs' => $this->em->getRepository(ActivityLog::class)->paginateActivities($page, $user),
            'profile' => $this->userService->checkUserProfile($user),
            'user' => $user,
        ]);
    }
}
