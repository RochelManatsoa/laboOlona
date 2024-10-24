<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Manager\MailManager;
use App\Entity\Finance\Devise;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Employe;
use App\Manager\ProfileManager;
use App\Manager\SimulatorManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Finance\Simulateur;
use App\Form\Finance\ContratHiddenType;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Finance\SimulateurEntrepriseType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/simulator')]
class SimulatorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private SimulatorManager $simulatorManager,
        private EmployeManager $employeManager,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
        private MailManager $mailManager,
        private PaginatorInterface $paginator,
    ){}

    #[Route('/', name: 'app_v2_recruiter_simulator')]
    public function index(Request $request): Response
    {   
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $simulations = $this->em->getRepository(Simulateur::class)->findSimulateursByUser($user);
        
        return $this->render('v2/dashboard/recruiter/simulator/index.html.twig', [
            'simulations' => $this->paginator->paginate(
                $simulations,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }

    #[Route('/create', name: 'app_v2_recruiter_simulator_create')]
    public function create(Request $request): Response
    {        
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $simulateur = $this->simulatorManager->init();
        $employe = $user->getEmploye();
        if(!$employe instanceof Employe){
            $employe = new Employe();
            $employe->setUser($user);
        }
        $simulateur->setEmploye($employe);
        $defaultDevise = $this->em->getRepository(Devise::class)->findOneBy(['slug' => 'euro']);
        $form = $this->createForm(SimulateurEntrepriseType::class, $simulateur, ['default_devise' => $defaultDevise]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message = "Simulation de salaire effectué";
            $success = true;
            $status = '<i class="bi bi-check-lg me-2"></i> Succès';
            $simulateur = null;
            $results = [];
            if($this->profileManager->canBuy($user, 10)){
                $simulateur = $form->getData();
                $employe = $simulateur->getEmploye();
                $user = $simulateur->getEmploye()->getUser();
                $results = $this->employeManager->simulate($simulateur);
                $employe->setNombreEnfants($form->get('nombreEnfant')->getData());
                $employe->setSalaireBase($results['salaire_de_base_ariary']);
    
                $this->em->persist($employe);
                $this->em->persist($simulateur);
                $this->em->flush();
                $this->creditManager->adjustCredits($user, 10, "Simulation de buget");
            }else{
                $message = "Crédits insuffisant. Veuillez recharger votre compte.";
                $success = false;
                $status = '<i class="bi bi-exclamation-octagon me-2"></i> Echec';
            }

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/simulator/live.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'credit' => $user->getCredit()->getTotal(),
                    'simulateur' => $simulateur,    
                    'results' => $results,
                ]);
            }

        }
        
        return $this->render('v2/dashboard/recruiter/simulator/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_v2_recruiter_simulator_view')]
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
            $this->addFlash('success', 'Demande d\'information envoyée, vous allez être rappelé dans les prochains jours.');
        }

        return $this->render('v2/dashboard/recruiter/simulator/view.html.twig', [
            'form' => $form->createView(),
            'simulateur' => $simulateur,    
            'results' => $results,
        ]);
    }
    
    #[Route('/delete/{simulator}', name: 'app_v2_recruiter_delete_simulator')]
    public function removeSimulator(Request $request, Simulateur $simulator): Response
    {
        $simulatorId = $simulator->getId();
        $message = "La simulator a bien été supprimée";
        $this->em->remove($simulator);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/simulator/delete.html.twig', [
                'simulatorId' => $simulatorId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_simulator');

    }
}
