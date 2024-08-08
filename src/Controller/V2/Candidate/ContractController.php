<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Finance\ContratRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/contract')]
class ContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ContratRepository $contratRepository,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_contract')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        
        return $this->render('v2/dashboard/candidate/contract/index.html.twig', [
            'contrats' => $this->contratRepository->findBy(['employe' => $user->getEmploye()]),
        ]);
    }
}
