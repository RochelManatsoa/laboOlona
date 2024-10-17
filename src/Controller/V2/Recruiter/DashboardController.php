<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Formation\Playlist;
use App\Entity\BusinessModel\Boost;
use App\Manager\AffiliateToolManager;
use App\Form\Boost\RecruiterBoostType;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use App\Repository\Formation\VideoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Formation\PlaylistRepository;
use App\Form\Search\AffiliateTool\ToolSearchType;
use App\Manager\BusinessModel\BoostVisibilityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private UserService $userService,
        private AffiliateToolManager $affiliateToolManager,
        private CreditManager $creditManager,
        private BoostVisibilityManager $boostVisibilityManager,
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

    #[Route('/boost-profile', name: 'app_v2_recruiter_boost_profile', methods: ['POST'])]
    public function boostProfile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();

        $form = $this->createForm(RecruiterBoostType::class, $recruiter); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $boostOptionFacebook = $form->get('boostFacebook')->getData(); 
            $recruiter = $form->getData();
            if ($boostOptionFacebook === 0) {
                $boostOptionFacebook = null; // Rendre null si la valeur est 0
            }
            // Vérifier si les deux boosts peuvent être appliqués
            $canApplyBoost = $this->profileManager->canApplyBoost($currentUser, $boostOption);
            $canApplyBoostFacebook = $this->profileManager->canApplyBoostFacebook($currentUser, $boostOptionFacebook);
            
            // Initialiser un tableau pour le résultat
            $result = [
                'success' => false,
                'status' => 'Echec',
                'detail' => '',
                'credit' => $currentUser->getCredit()->getTotal(),
            ];

            // Vérifier si les boosts peuvent être appliqués
            $canApplyBoost = $this->profileManager->canApplyBoost($currentUser, $boostOption);
            $canApplyBoostFacebook = $this->profileManager->canApplyBoostFacebook($currentUser, $boostOptionFacebook);

            // Vérification des crédits
            if ($boostOptionFacebook && !($canApplyBoost && $canApplyBoostFacebook)) {
                // Si le boost Facebook est demandé mais crédits insuffisants pour les deux
                $result['message'] = 'Crédits insuffisants pour appliquer les deux boosts.';
            } elseif ($canApplyBoost) {
                if ($boostOptionFacebook && $canApplyBoostFacebook) {
                    $resultProfile = $this->handleBoostProfile($boostOption, $recruiter, $currentUser);
                    $resultFacebook = $this->handleBoostFacebook($boostOptionFacebook, $recruiter, $currentUser);

                    $result['message'] = $resultProfile['message'] . ' ' . $resultFacebook['message'];
                    $result['success'] = $resultProfile['success'] && $resultFacebook['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fs-6 fw-bold text-uppercase"><i class="bi bi-facebook me-2"></i> Boost facebook</span><br><span class="small fw-light"> Jusqu\'au '.$resultFacebook['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                        <div class="text-center"><span class="fs-6 fw-bold text-uppercase"><i class="bi bi-rocket me-2"></i> Profil boosté</span><br><span class="small fw-light"> Jusqu\'au '.$resultProfile['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                    ';
                } else {
                    $resultProfile = $this->handleBoostProfile($boostOption, $recruiter, $currentUser);
                    $result['message'] = $resultProfile['message'];
                    $result['success'] = $resultProfile['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fs-6 fw-bold text-uppercase"><i class="bi bi-rocket me-2"></i> Profil boosté</span><br><span class="small fw-light"> Jusqu\'au '.$resultProfile['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                    ';
                }
                $result['status'] = $result['success'] ? 'Succès' : 'Echec';
            } else {
                $result['message'] = 'Crédits insuffisants pour le boost de profil.';
            }

            return $this->json($result, 200);
        }

        return $this->json([
            'status' => 'error', 
            'message' => 'Erreur de formulaire.'
        ], 400);    
    }

    private function handleBoostProfile($boostOption, $recruiter, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
        ->findBoostVisibilityByBoostAndUser($boostOption, $currentUser);
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOption->getCredit());
        
        if (isset($response['success'])) {
            $recruiter->setStatus(EntrepriseProfile::STATUS_PREMIUM);
            $recruiter->setBoost($boostOption);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($currentUser);
            $this->em->persist($recruiter);
            $this->em->flush();
            return [
                'message' => 'Votre profil est maintenant boosté',
                'success' => true,
                'status' => 'Succès',
                'visibilityBoost' => $visibilityBoost
            ];
        } else {
            return [
                'message' => 'Une erreur s\'est produite.',
                'success' => false,
                'status' => 'Echec'
            ];
        }
    }

    private function handleBoostFacebook($boostOptionFacebook, $recruiter, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
            ->findBoostVisibilityByBoostFacebookAndCandidate($boostOptionFacebook, $currentUser);

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit());

        if (isset($response['success'])) {
            $recruiter->setStatus(EntrepriseProfile::STATUS_PREMIUM);
            $recruiter->setBoostFacebook($boostOptionFacebook);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($currentUser);
            $this->em->persist($recruiter);
            $this->em->flush();

            return [
                'message' => 'Votre profil est maintenant boosté sur facebook',
                'success' => true,
                'status' => 'Succès',
                'visibilityBoost' => $visibilityBoost
            ];
        } else {
            return [
                'message' => 'Une erreur s\'est produite.',
                'success' => false,
                'status' => 'Echec'
            ];
        }
    }
}
