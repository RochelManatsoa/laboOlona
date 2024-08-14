<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\Finance\Contrat;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/contract')]
class ContractController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_contract')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $contracts = $this->em->getRepository(Contrat::class)->findContractsByUser($user);
        
        return $this->render('v2/dashboard/recruiter/contract/index.html.twig', [
            'contracts' => $this->paginator->paginate(
                $contracts,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }
}
