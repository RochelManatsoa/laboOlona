<?php

namespace App\Controller\Dashboard;

use App\Entity\Candidate\TarifCandidat;
use App\Entity\CandidateProfile;
use App\Manager\MailManager;
use App\Entity\Finance\Contrat;
use App\Form\Finance\EmployeType;
use App\Service\User\UserService;
use App\Entity\Finance\Simulateur;
use App\Entity\Entreprise\JobListing;
use App\Form\Finance\ContratHiddenType;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Finance\ContratRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Finance\SimulateurRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/employes-hub')]
class EmployesController extends AbstractController
{    
    public function __construct(
        private EmployeManager $employeManager,
        private RequestStack $requestStack,
        private UserService $userService,
        private MailManager $mailManager,
        private EntityManagerInterface $em,
        private PaginatorInterface $paginatorInterface,
        private SimulateurRepository $simulateurRepository,
        private ContratRepository $contratRepository,
        private JobListingRepository $jobListingRepository,
    ){}

    #[Route('/', name: 'app_dashboard_employes')]
    public function index(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        $session = $this->requestStack->getSession();
        
        return $this->render('dashboard/employes/index.html.twig', [
            'simulateurs' => $this->simulateurRepository->findAll(),
        ]);
    }

    #[Route('/simulations', name: 'app_dashboard_employes_simulations')]
    public function simulations(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if (!$candidat instanceof CandidateProfile) {
            $candidat = false;
        }
        $simulateurs = $this->simulateurRepository->findBy([
            'employe' => $user->getEmploye(),
            'isDeleted' => null
        ],['id' => 'DESC']);


        if ($request->isMethod('POST')) {
            $simulationId = $request->request->get('simulation');
            $simulation = $this->simulateurRepository->find($simulationId);
            if ($simulation) {
                $user->getCandidateProfile()->getTarifCandidat()
                ->setMontant($simulation->getSalaireNet())
                ->setTypeTarif(TarifCandidat::TYPE_MONTHLY)
                ->setCurrency($simulation->getDevise())
                ->setDevise($simulation->getDeviseSymbole())
                ->setSimulation($simulation);
                $this->em->flush();
                $this->addFlash('success', 'Le tarif du candidat a été mis à jour.');
            }
        }

        return $this->render('dashboard/employes/simulations.html.twig', [
            'simulateurs' => $simulateurs,
            'candidat' => $candidat,
        ]);
    }

    #[Route('/simulation/view/{id}', name: 'app_dashboard_employes_simulation_view')]
    public function view(Request $request, Simulateur $simulateur): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);

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
            $this->addFlash('success', 'Demande d\'information envoyée, vous allez être rappelé dans les prochains jours.');
        }

        return $this->render('dashboard/employes/view.html.twig', [
            'form' => $form->createView(),
            'simulateur' => $simulateur,    
            'results' => $results,
        ]);
    }

    #[Route('/simulation/delete/{id}', name: 'app_dashboard_employes_simulation_delete')]
    public function delete(Request $request, Simulateur $simulateur): Response
    {
        /** @var User $user */
        $simulateur->setIsDeleted(true);
        $this->em->persist($simulateur);
        $this->em->flush($simulateur);

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_employes_simulations');
    }

    #[Route('/contrats', name: 'app_dashboard_employes_contrats')]
    public function contrats(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        $session = $this->requestStack->getSession();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/employes/contrats.html.twig', [
            'contrats' => $this->contratRepository->findBy(['employe' => $user->getEmploye()]),
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_employes_annonces')]
    public function annonces(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        $data = $this->jobListingRepository->findAllJobListingPublished();

        return $this->render('dashboard/employes/annonces.html.twig', [
            'annonces' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }
    
    #[Route('/annonce/{jobId}', name: 'app_dashboard_employes_annonce_view')]
    public function annonce(Request $request, JobListing $annonce): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        return $this->render('dashboard/employes/annonce/view.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/infos', name: 'app_dashboard_employes_infos')]
    public function infos(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];

        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
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
