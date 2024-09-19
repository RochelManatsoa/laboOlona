<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Data\Profile\RecrutementSearchData;
use App\Form\Moderateur\RecrutementType;
use App\Service\User\UserService;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/dashboard/moderateur/recrutement')]
class RecrutementController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private CandidateProfileRepository $candidateProfileRepository,
    ) {}

    #[Route('/', name: 'app_dashboard_moderateur_recrutement')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new RecrutementSearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(RecrutementType::class, $data);
        $form->handleRequest($request);
        $candidats = $this->candidateProfileRepository->findRecrutSearch($data);

        return $this->render('dashboard/moderateur/recrutement/index.html.twig', [
            'form' => $form->createView(),
            'candidats' => $candidats,
        ]);
    }
}
