<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\Finance\Devise;
use App\Form\Finance\DeviseType;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Search\Devise\DeviseSearchType;
use App\Repository\Finance\DeviseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class DeviseController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private DeviseRepository $deviseRepository,
        private PaginatorInterface $paginatorInterface,
        private ModerateurManager $moderateurManager,
    ) {}
    
    #[Route('/devises', name: 'app_dashboard_moderateur_devise')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Formulaire de recherche secteur */
        $form = $this->createForm(DeviseSearchType::class);
        $form->handleRequest($request);
        $data = $this->deviseRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('devise')->getData();
            $data = $this->moderateurManager->searchDevise($searchTerm);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/devise/_devises.html.twig', [
                        'devises' => $this->paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/devise/index.html.twig', [
            'devises' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/devise/new', name: 'app_dashboard_moderateur_new_devise')]
    public function newDevise(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Initialiser une instance de devise */
        $devise = $this->moderateurManager->initDevise();
        $form = $this->createForm(DeviseType::class, $devise);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder TypeContrat */
            $devise = $this->moderateurManager->saveDeviseForm($form);
            $this->addFlash('success', 'Devise sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_devise', []);
        }

        return $this->render('dashboard/moderateur/devise/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
        ]);
    }

    #[Route('/devise/edit/{id}', name: 'app_dashboard_moderateur_edit_devise')]
    public function editDevise(Request $request, Devise $devise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Initialiser une instance de devise */
        $form = $this->createForm(DeviseType::class, $devise);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder TypeContrat */
            $devise = $this->moderateurManager->saveDeviseForm($form);
            $this->addFlash('success', 'Devise sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_devise', []);
        }

        return $this->render('dashboard/moderateur/devise/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Mettre à jour',
        ]);
    }
}
