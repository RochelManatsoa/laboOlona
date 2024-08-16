<?php

namespace App\Controller\V2\Recruiter;

use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Entity\Formation\Playlist;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Formation\VideoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Formation\PlaylistRepository;
use App\Form\Search\AffiliateTool\ToolSearchType;
use App\Manager\AffiliateToolManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private UserService $userService,
        private AffiliateToolManager $affiliateToolManager,
    ){}

    #[Route('/', name: 'app_v2_recruiter_dashboard')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        $form = $this->createForm(EditEntrepriseType::class, $recruiter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($recruiter);
            $this->em->flush();
            $this->addFlash('success', 'Profil modifié avec succès.');

            return $this->redirectToRoute('app_v2_recruiter_dashboard');
        }

        return $this->render('v2/dashboard/recruiter/index.html.twig', [
            'form' => $form->createView(),
            'recruiter' => $recruiter,
        ]);
    }

    #[Route('/centre-de-formation', name: 'app_v2_recruiter_dashboard_formation')]
    public function formation(PlaylistRepository $playlistRepository, VideoRepository $videoRepository): Response
    {
        return $this->render('v2/dashboard/recruiter/formation.html.twig', [
            'playlists' => $playlistRepository->findAll(),
            'videos' => $videoRepository->findAll(),
        ]);
    }

    #[Route('/centre-de-formation/playlist/{id}', name: 'app_v2_recruiter_dashboard_formation_playlist_view')]
    public function viewPlaylist(Playlist $playlist): Response
    {
        return $this->render('v2/dashboard/recruiter/_playlist.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/outils-ai', name: 'app_v2_recruiter_dashboard_ai_tools')]
    public function aiTools(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $form = $this->createForm(ToolSearchType::class);
        $form->handleRequest($request);
        $data = $this->affiliateToolManager->findAllAITools();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $data = $this->affiliateToolManager->findSearchTools($nom);
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'content' => $this->renderView('dashboard/moderateur/affiliate_tool/_aitools.html.twig', [
                        'aiTools' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ], 200);
            }
        }
        return $this->render('v2/dashboard/recruiter/ai_tools.html.twig', [
            'aiTools' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);

    }
}
