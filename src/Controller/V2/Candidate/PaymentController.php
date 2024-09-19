<?php

namespace App\Controller\V2\Candidate;

use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_v2_candidate_payment')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();

        return $this->render('v2/dashboard/candidate/payment/index.html.twig', []);
    }
}
