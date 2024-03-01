<?php

namespace App\Controller\Finance;

use App\Entity\Finance\Contrat;
use App\Form\Finance\ContratType;
use App\Manager\Finance\EmployeManager;
use App\Repository\Finance\ContratRepository;
use App\Service\User\UserService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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
        return $this->render('finance/contrat/index.html.twig', [
            'contrats' => $this->paginatorInterface->paginate(
                $this->contratRepository->findAll([
                    'id' => 'DESC'
                ]),
                $request->query->getInt('page', 1),
                10
            ),
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
