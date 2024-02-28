<?php

namespace App\Controller\Dashboard;

use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Form\Finance\ContratHiddenType;
use App\Form\Finance\EmployeType;
use App\Service\User\UserService;
use App\Manager\Finance\EmployeManager;
use App\Manager\MailManager;
use App\Repository\Finance\ContratRepository;
use App\Repository\Finance\SimulateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/employes-hub')]
class EmployesController extends AbstractController
{    
    public function __construct(
        private EmployeManager $employeManager,
        private RequestStack $requestStack,
        private UserService $userService,
        private MailManager $mailManager,
        private EntityManagerInterface $em,
        private SimulateurRepository $simulateurRepository,
        private ContratRepository $contratRepository,
    ){}

    #[Route('/', name: 'app_dashboard_employes')]
    public function index(): Response
    {
        $session = $this->requestStack->getSession();
        dump($session);
        return $this->render('dashboard/employes/index.html.twig', [
            'simulateurs' => $this->simulateurRepository->findAll(),
        ]);
    }

    #[Route('/simulations', name: 'app_dashboard_employes_simulations')]
    public function simulations(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/employes/simulations.html.twig', [
            'simulateurs' => $this->simulateurRepository->findBy(['employe' => $user->getEmploye()]),
        ]);
    }

    #[Route('/simulation/view/{id}', name: 'app_dashboard_employes_simulation_view')]
    public function view(Request $request, Simulateur $simulateur): Response
    {
        $results = $this->employeManager->simulate($simulateur);
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $employe = $user->getEmploye();
        $contrat = new Contrat();
        $contrat->setSimulateur($simulateur);
        $contrat->setEmploye($employe);
        $contrat->setSalaireBase($results['salaire_de_base_euro']);
        $contrat->setStatus(Contrat::STATUS_PENDING);
        $form = $this->createForm(ContratHiddenType::class, $contrat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $contrat = $form->getData();
            $this->em->persist($contrat);
            $this->em->flush();
            /** Envoi mail */
            $this->mailManager->newPortage($contrat->getEmploye()->getUser(), $contrat);
            $this->addFlash('success', 'Demande d\'information envoyée, vous allez être rappeleé dans les prochains jour');
        }

        return $this->render('dashboard/employes/view.html.twig', [
            'form' => $form->createView(),
            'simulateur' => $simulateur,
            'results' => $results,
        ]);
    }

    #[Route('/contrats', name: 'app_dashboard_employes_contrats')]
    public function contrats(): Response
    {
        $session = $this->requestStack->getSession();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/employes/contrats.html.twig', [
            'contrats' => $this->contratRepository->findBy(['employe' => $user->getEmploye()]),
        ]);
    }

    #[Route('/infos', name: 'app_dashboard_employes_infos')]
    public function infos(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $form = $this->createForm(EmployeType::class, $user->getEmploye());
        $form->handleRequest($request);

        return $this->render('dashboard/employes/infos.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
