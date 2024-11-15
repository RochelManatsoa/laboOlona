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
        $data = new UserData($request->query->get('startDate'), $request->query->get('endDate'), $request->query->getInt('page', 1), $request->query->getInt('days', 1));
        
        /** Formulaire de recherche entreprise */
        $form = $this->createForm(UserDataType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $startDate = $form->get('startDate')->getData();
            $endDate = $form->get('endDate')->getData();
            $page = $form->get('page')->getData() ?? 1;
            $days = $form->get('days')->getData() ?? 1;
            
            $data = new UserData($startDate, $endDate, $page, $days);
        }
        // dd($data);

        return $this->render('v2/staff/history/index.html.twig', [
            'users' => $this->em->getRepository(User::class)->paginateUsers($data),
            'form' => $form->createView(),
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

class HistoryData
{
    private int $startDate;
    private int $endDate;
    private int $page;

    public function __construct(int $startDate, int $endDate, int $page)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->page = $page;
    }

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    public function getEndDate(): int
    {
        return $this->endDate;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
