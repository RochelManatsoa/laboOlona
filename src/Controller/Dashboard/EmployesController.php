<?php

namespace App\Controller\Dashboard;

use App\Entity\Finance\Simulateur;
use App\Form\Finance\EmployeType;
use App\Service\User\UserService;
use App\Manager\Finance\EmployeManager;
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
        private EntityManagerInterface $em,
        private SimulateurRepository $simulateurRepository,
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
    public function view(Simulateur $simulateur): Response
    {
        return $this->render('dashboard/employes/view.html.twig', [
            'simulateur' => $simulateur,
            'results' => $this->employeManager->simulate($simulateur),
        ]);
    }

    #[Route('/contrats', name: 'app_dashboard_employes_contrats')]
    public function contrats(): Response
    {
        $session = $this->requestStack->getSession();

        return $this->render('dashboard/employes/contrats.html.twig', [
            'contrats' => [],
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
