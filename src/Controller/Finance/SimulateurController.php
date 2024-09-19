<?php

namespace App\Controller\Finance;

use App\Data\Finance\SearchData;
use App\Entity\Finance\Simulateur;
use App\Form\Simulateur\SearchForm;
use App\Repository\Finance\SimulateurRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/finance/simulateur')]
class SimulateurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PaginatorInterface $paginatorInterface,
        private SimulateurRepository $simulateurRepository,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_finance_simulateur')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('FINANCE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new SearchData;
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchForm::class, $data);
        $form->handleRequest($request);
        $simulations = $this->simulateurRepository->findSearch($data);
        
        return $this->render('finance/simulateur/index.html.twig', [
            'simulations' => $simulations,
            'form' => $form->createView(),
        ]);
    }
}
