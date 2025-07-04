<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\EntrepriseProfile;
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
use App\Manager\AssignationManager;
use App\Repository\Moderateur\AssignationRepository;
use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/assignation')]
class AssignationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private AssignationRepository $assignationRepository,
        private AssignationManager $assignationManager,
        private PaginatorInterface $paginatorInterface,
        private UserService $userService,
        private AppExtension $appExtension,
    ) {}

    #[Route('/', name: 'app_dashboard_moderateur_assignation')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
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
            $assignationForms[$profil->getId()] = [
                'form' => $assignationForm->createView(),
                'formName' => $formName
            ];
        }
    
        return $this->render('dashboard/moderateur/assignation/index.html.twig', [
            'assignations' => $this->assignationRepository->findBy([], ['id' => 'DESC']),
            'attentes' => $this->assignationManager->getGroupedBy(
                Assignation::STATUS_PENDING
            ),
            'acceptees' => $this->assignationManager->getGroupedBy(
                Assignation::STATUS_ACCEPTED
            ),
            'refusees' => $this->assignationManager->getGroupedBy(
                Assignation::STATUS_REFUSED
            ),
            'moderees' => $this->assignationManager->getGroupedBy(
                Assignation::STATUS_MODERATED
            ),
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
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $assignation = new Assignation();
        $form = $this->createForm(AssignationFormType::class, $assignation);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($assignation);
            $this->em->flush();
        }

        return $this->render('dashboard/moderateur/assignation/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/view/{id}', name: 'app_dashboard_moderateur_assignation_view')]
    public function viewAssign(Request $request, Assignation $assignation): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(AssignationFormType::class, $assignation);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitez l'assignation ici
            $this->em->persist($assignation);
            $this->em->flush();
        }

        return $this->render('dashboard/moderateur/assignation/view.html.twig', [
            'assignation' => $assignation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{id}', name: 'app_dashboard_moderateur_assignation_entreprise')]
    public function entrepriseAssign(Request $request, EntrepriseProfile $entreprise): Response
    {        
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = $this->appExtension->getAssignByEntreprise($entreprise);

        /** Formulaire de recherche entreprise */
        // $form = $this->createForm(ModerateurAnnonceEntrepriseSearchType::class);
        // $form->handleRequest($request);
        // $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, null, null, $status);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $nom = $form->get('nom')->getData();
        //     $status = $form->get('status')->getData();
        //     $secteur = $form->get('secteur')->getData();
        //     $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, $nom, $secteur, $status);
        //     if ($request->isXmlHttpRequest()) {
        //         // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
        //         return new JsonResponse([
        //             'content' => $this->renderView('dashboard/moderateur/assignation/_entreprise.html.twig', [
        //                 'annonces' => $paginatorInterface->paginate(
        //                     $data,
        //                     $request->query->getInt('page', 1),
        //                     10
        //                 ),
        //                 'result' => $data
        //             ])
        //         ]);
        //     }
        // }

        return $this->render('dashboard/moderateur/assignation/entreprise.html.twig', [
            'annonces' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'entreprise' => $entreprise,
            // 'form' => $form->createView(),
        ]);
    }
}
