<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\TypeContrat;
use App\Form\Moderateur\TypeContratType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\Moderateur\TypeContratRepository;
use App\Form\Search\TypeContrat\TypeContratSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class TypeContratController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
    ) {}
    
    #[Route('/type-contrat', name: 'app_dashboard_moderateur_type_contrat')]
    public function typeContrat(Request $request, TypeContratRepository $typeContratRepository, PaginatorInterface $paginatorInterface): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Formulaire de recherche type de contrat */
        $form = $this->createForm(TypeContratSearchType::class);
        $form->handleRequest($request);
        $data = $typeContratRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('typeContrat')->getData();
            $data = $this->moderateurManager->searchTypeContrat($searchTerm);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/type-contrat/_type_contrats.html.twig', [
                        'types_contrat' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }
        
        return $this->render('dashboard/moderateur/type-contrat/index.html.twig', [
            'types_contrat' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/type-contrat/new', name: 'app_dashboard_moderateur_new_type_contrat')]
    public function newTypeContrat(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Initialiser une instance de TypeContrat */
        $typeContrat = $this->moderateurManager->initTypeContrat();
        $form = $this->createForm(TypeContratType::class, $typeContrat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder TypeContrat */
            $typeContrat = $this->moderateurManager->saveTypeContratForm($form);
            $this->addFlash('success', 'Type contrat sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_type_contrat', []);
        }

        return $this->render('dashboard/moderateur/type-contrat/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
        ]);
    }

    #[Route('/type-contrat/{slug}/edit', name: 'app_dashboard_moderateur_edit_type_contrat')]
    public function editTypeContrat(Request $request, TypeContrat $typeContrat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var TypeContrat $typeContrat qui vient de {slug} */
        $form = $this->createForm(TypeContratType::class, $typeContrat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Mettre à jour le TypeContrat */
            $typeContrat = $this->moderateurManager->saveTypeContratForm($form);
            $this->addFlash('success', 'Type contrat mis à jour');

            return $this->redirectToRoute('app_dashboard_moderateur_type_contrat', []);
        }

        return $this->render('dashboard/moderateur/type-contrat/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Mettre à jour',
        ]);
    }

    #[Route('/type-contrat/supprimer/{slug}', name: 'app_dashboard_moderateur_delete_type_contrat')]
    public function deleteTypeContrat(TypeContrat $typeContrat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Supprimer le TypeContrat */
        $this->moderateurManager->deleteTypeContrat($typeContrat);
        $this->addFlash('success', 'Type contrat supprimé avec succès.');

        return $this->redirectToRoute('app_dashboard_moderateur_type_contrat');
    }
}
