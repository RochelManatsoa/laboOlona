<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Finance\Contrat;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Finance\ContratRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
class ContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ContratRepository $contratRepository,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/contracts', name: 'app_v2_contracts')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $contracts = $this->em->getRepository(Contrat::class)->findContractsByUser($user);
        
        return $this->render('v2/dashboard/contract/index.html.twig', [
            'contracts' => $this->paginator->paginate(
                $contracts,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }
}
