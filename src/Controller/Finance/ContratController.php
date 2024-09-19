<?php

namespace App\Controller\Finance;

use DateTime;
use App\Entity\Finance\Contrat;
use App\Data\Finance\SearchData;
use App\Form\Finance\ContratType;
use App\Service\User\UserService;
use App\Form\Simulateur\SearchForm;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Finance\ContratRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/finance/contrat')]
class ContratController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ContratRepository $contratRepository,
        private PaginatorInterface $paginatorInterface,
        private EmployeManager $employeManager,
    ) {
    }

    #[Route('/', name: 'app_finance_contrat')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('FINANCE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new SearchData;
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchForm::class, $data);
        $form->handleRequest($request);
        $demandes = $this->contratRepository->findSearch($data);

        return $this->render('finance/contrat/index.html.twig', [
            'contrats' => $demandes,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_finance_contrat_view')]
    public function view(Request $request, Contrat $contrat): Response
    {
        $form = $this->createForm(ContratType::class, $contrat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $contrat = $form->getData();
            $contrat->setUpdatedAt(new DateTime());
            $this->em->persist($contrat);
            $this->em->flush();
            $this->addFlash('success', 'Contrat a bien été mis à jour');
            return $this->redirectToRoute('app_finance_contrat_view', ['id' => $contrat->getId()]);
        }

        return $this->render('finance/contrat/view.html.twig', [
            'contrat' => $contrat,
            'form' => $form->createView(),
            'simulateur' => $contrat->getSimulateur(),
            'results' => $this->employeManager->simulate($contrat->getSimulateur()),
        ]);
    }
}
