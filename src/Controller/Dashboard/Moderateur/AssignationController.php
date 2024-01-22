<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Entity\Moderateur\Assignation;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Moderateur\AssignationFormType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\AssignateProfileFormType;
use App\Repository\Moderateur\AssignationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/assignation')]
class AssignationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private AssignationRepository $assignationRepository,
        private PaginatorInterface $paginatorInterface,
        private UserService $userService,
    )
    {}

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_connect');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_moderateur_assignation')]
    public function index(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
    
        $profils = $this->candidateProfileRepository->findAllValid();
        $entreprises = $this->entrepriseProfileRepository->findAll();
        $assignationForms = [];
    
        foreach ($profils as $profil) {
            $formOptions = [
                'entreprises' => $entreprises,
            ];
            $formName = 'assignation_form_' . $profil->getId();
            $assignationForm = $this->createForm(AssignateProfileFormType::class, $profil,  [
                'form_id' => $formName
            ]);
            $assignationForm->handleRequest($request);
            // dump($assignationForm);
            $assignationForms[$profil->getId()] = [
                'form' => $assignationForm->createView(),
                'formName' => $formName
            ];
        }
        // dd($assignationForms);
    
        return $this->render('dashboard/moderateur/assignation/index.html.twig', [
            'affectations' => $this->assignationRepository->findAll(),
            'profils' => $this->paginatorInterface->paginate(
                $profils,
                $request->query->getInt('page', 1),
                10
            ),
            'assignationForms' => $assignationForms 
        ]);
    }

    #[Route('/new', name: 'app_dashboard_moderateur_assignation_new')]
    public function newAssign(Request $request): Response
    {
        $assignation = new Assignation();
        $form = $this->createForm(AssignationFormType::class, $assignation);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitez l'assignation ici
            // dd($form->getData());
            $this->em->persist($assignation);
            $this->em->flush();
        }

        return $this->render('dashboard/moderateur/assignation/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
