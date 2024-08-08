<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Service\User\UserService;
use App\Manager\RendezVousManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/metting')]
class MettingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private RendezVousManager $rendezVousManager,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_metting')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $role = $this->rendezVousManager->getUserRole($user);
        
        return $this->render('v2/dashboard/candidate/metting/index.html.twig', [
            'rendezvousList' => $this->rendezVousManager->findMettingByRole($role),
            'role' => $user->getType(),
        ]);
    }
}
