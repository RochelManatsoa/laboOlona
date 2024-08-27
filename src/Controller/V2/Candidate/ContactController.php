<?php

namespace App\Controller\V2\Candidate;

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

#[Route('/v2/candidate/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ContratRepository $contratRepository,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_contact')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $purchasedContacts = $user->getPurchasedContacts();
        
        return $this->render('v2/dashboard/candidate/contact/index.html.twig', [
            'contacts' => $this->paginator->paginate(
                $purchasedContacts,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }
}
