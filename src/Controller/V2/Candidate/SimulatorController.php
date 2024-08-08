<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Manager\MailManager;
use App\Entity\Finance\Devise;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Employe;
use App\Service\User\UserService;
use App\Entity\Finance\Simulateur;
use App\Form\Finance\SimulateurType;
use App\Form\Finance\ContratHiddenType;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/simulator')]
class SimulatorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private EmployeManager $employeManager,
        private MailManager $mailManager,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_simulator')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $simulateur = (new Simulateur())->setCreatedAt(new \DateTime());
        $employe = $user->getEmploye();
        if(!$employe instanceof Employe){
            $employe = new Employe();
            $employe->setUser($user);
        }
        $simulateur->setEmploye($employe);
        $defaultDevise = $this->em->getRepository(Devise::class)->findOneBy(['slug' => 'euro']);
        $form = $this->createForm(SimulateurType::class, $simulateur, ['default_devise' => $defaultDevise]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->employeManager->simulate($simulateur);
            $simulateur = $form->getData();
            $employe = $simulateur->getEmploye();
            $user = $simulateur->getEmploye()->getUser();
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if($existingUser instanceof User){
                $currentRoles = $existingUser->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $existingUser->setRoles($currentRoles);
                $this->em->persist($existingUser);
            }else{
                $currentRoles = $user->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $user->setRoles($currentRoles);
                $this->em->persist($user);
            }
            $employe->setNombreEnfants($form->get('nombreEnfant')->getData());
            $employe->setSalaireBase($result['salaire_de_base_ariary']);

            $this->em->persist($employe);
            $this->em->persist($simulateur);
            $this->em->flush();

            return $this->redirectToRoute('app_v2_candidate_simulator_view', ['id' => $simulateur->getId()]);
        }
        return $this->render('v2/dashboard/candidate/simulator/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_v2_candidate_simulator_view')]
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

        return $this->render('v2/dashboard/candidate/simulator/view.html.twig', [
            'form' => $form->createView(),
            'simulateur' => $simulateur,    
            'results' => $results,
        ]);
    }
}
