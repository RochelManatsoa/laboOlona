<?php

namespace App\Controller\Dashboard\Entreprise;

use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Manager\EntrepriseManager;
use App\Entity\Entreprise\JobListing;
use App\Entity\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\Search\Candidature\EntrepriseCandidatureSearchType;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

#[Route('/dashboard/entreprise')]
class CandidatureController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private EntrepriseManager $entrepriseManager,
        private PaginatorInterface $paginatorInterface,
        private MettingRepository $mettingRepository,
        private JobListingRepository $jobListingRepository,
    ) {
    }
    
    private function checkEntreprise()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, ' Si vous avez la moindre question ou si vous souhaitez un accompagnement personnalisé, n\'hésitez surtout pas à nous envoyer un message instantané directement sur nos réseaux sociaux. Nous sommes ici pour vous accompagner à chaque étape vers le succès.');

        return null;
    }
    
    #[Route('/candidatures', name: 'app_dashboard_entreprise_candidatures')]
    public function index(Request $request): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        $form = $this->createForm(EntrepriseCandidatureSearchType::class);
        $form->handleRequest($request);

        $data = $this->entrepriseManager->findAllCandidature();
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $data = $this->entrepriseManager->findAllCandidature($titre);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/entreprise/candidature/_candidatures.html.twig', [
                        'applications' => $this->paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data,
                        'count' => $this->entrepriseManager->countElements($data),
                        'meetings' => $this->mettingRepository->findBy(['entreprise' => $entreprise]),
                    ]),
                ]);
            }
        }

        return $this->render('dashboard/entreprise/candidature/index.html.twig', [
            'annonces' => $entreprise->getJobListings(),
            'applications' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'form' => $form->createView(),
            'meetings' => $this->mettingRepository->findBy(['entreprise' => $entreprise]),
            'result' => $data,
            'count' => $this->entrepriseManager->countElements($data),
        ]);
    }

    #[Route('/candidature/{id}/view', name: 'app_dashboard_entreprise_view_candidature')]
    public function candidatureView(Request $request, Applications $applications): Response
    {
        $this->checkEntreprise();

        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/entreprise/candidature/view.html.twig', [
            'application' => $applications,
            'entreprise' => $user->getEntrepriseProfile(),
        ]);
    }

    #[Route('/candidature/view/annonce/{id}', name: 'app_dashboard_moderateur_candidature_annonce_view')]
    public function candidatureAnnonce(Request $request, JobListing $annonce): Response
    {
        $this->checkEntreprise();
        
        return $this->render('dashboard/entreprise/candidature/annonce.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/candidature/view/annonce/{id}/suggestions', name: 'app_dashboard_moderateur_candidature_annonce_view_suggest')]
    public function candidatureAnnonceSuggest(Request $request, JobListing $annonce): Response
    {
        $this->checkEntreprise();
        
        return $this->render('dashboard/entreprise/candidature/suggest.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/candidature/view/annonce/{id}/defaut', name: 'app_dashboard_moderateur_candidature_annonce_view_default')]
    public function candidatureAnnonceDefailt(Request $request, JobListing $annonce): Response
    {
        $this->checkEntreprise();
        
        return $this->render('dashboard/entreprise/candidature/default.html.twig', [
            'annonce' => $annonce,
        ]);
    }
}
