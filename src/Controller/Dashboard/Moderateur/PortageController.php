<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Entity\Secteur;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Search\Secteur\SecteurSearchType;
use App\Manager\Finance\EmployeManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class PortageController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private EmployeManager $employeManager,
    ) {}

    #[Route('/portages', name: 'app_dashboard_moderateur_portage')]
    public function portage(Request $request, SecteurRepository $secteurRepository, PaginatorInterface $paginatorInterface): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Formulaire de recherche secteur */
        $form = $this->createForm(SecteurSearchType::class);
        $form->handleRequest($request);
        $data = $secteurRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('secteur')->getData();
            $data = $this->moderateurManager->searchSecteur($searchTerm);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/secteur/_secteurs.html.twig', [
                        'secteurs' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/portage/index.html.twig', [
            'secteurs' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'portages' => $this->em->getRepository(Contrat::class)->findBy([], ['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/portage/{id}/view', name: 'app_dashboard_moderateur_view_portage')]
    public function viewPortage(Request $request, Contrat $contrat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $simulateur = $contrat->getSimulateur();

        return $this->render('dashboard/moderateur/portage/view.html.twig', [
            'portage' => $contrat,
            'simulateur' => $simulateur,
            'results' => $this->employeManager->simulate($simulateur),
            'button_label' => 'Mettre à jour',
        ]);
    }
}
