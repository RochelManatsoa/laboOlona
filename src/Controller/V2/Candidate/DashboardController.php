<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Formation\Playlist;
use App\Entity\BusinessModel\Credit;
use App\Manager\AffiliateToolManager;
use App\Form\Boost\CandidateBoostType;
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
use App\Form\Profile\Candidat\CandidateUploadType;
use App\Manager\BusinessModel\BoostVisibilityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Controller\Dashboard\Moderateur\OpenAi\CandidatController;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\CandidateProfile;
use App\Form\Profile\Candidat\Edit\EditCandidateProfile as EditStepOneType;

#[Route('/v2/candidate/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
        private BoostVisibilityManager $boostVisibilityManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private CandidatController $candidatController,
        private CandidatManager $candidatManager,
        private AffiliateToolManager $affiliateToolManager,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_dashboard')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $formOne = $this->createForm(EditStepOneType::class, $candidat);
        $formOne->handleRequest($request);

        $form = $this->createForm(CandidateUploadType::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $candidat);
            }
            $this->em->persist($candidat);
            $this->em->flush();
            $this->em->getConnection()->close();
            
            $message = 'Analyse de CV effectué avec succès';
            $success = true;
            $status = 'Succès';
            $upload = false;
            $creditAmount = 0;
            if($candidat->isIsGeneretated()){
                $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_UPLOAD_CV);
            }

            if($this->profileManager->canApplyAction($currentUser, Credit::ACTION_UPLOAD_CV)){
                $responseOpenai = $this->candidatController->analyse(new \Symfony\Component\HttpFoundation\Request(), $candidat);
                $content = $responseOpenai->getContent(); 
                $data = json_decode($content, true);
                if($data['status'] === 'error'){
                    $message = "Une erreur s'est produite. Notre IA n'arrive pas à acceder à votre CV";
                    $success = false;
                    $status = 'Echec';
                    $upload = false;
                }else{
                    $response = $this->creditManager->adjustCredits($this->userService->getCurrentUser(), $creditAmount);
                    if (isset($response['error'])) {
                        $message = $response['error'];
                        $success = false;
                        $status = 'Echec';
                        $upload = false;
                    }
                    $message = $data['message'];
                    $success = true;
                    $upload = true;
                    $status = 'Succès';
                }
            }else{
                $message = "Crédits insuffisant. Veuillez recharger votre compte.";
                $success = false;
                $status = 'Echec';
                $upload = false;
            }

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/candidate/update.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'upload' => $upload,
                    'credit' => $currentUser->getCredit()->getTotal(),
                    'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
                    'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
                    'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
                ]);
            }
            
            return $this->json([
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'credit' => $currentUser->getCredit()->getTotal(),
            ], 200);
        }
        if($formOne->isSubmitted() && $formOne->isValid()){
            $this->em->persist($candidat);
            $this->em->flush();

            $this->addFlash('success', 'Informations enregistrées');
        }

        return $this->render('v2/dashboard/candidate/index.html.twig', [
            'form' => $form->createView(),
            'form_one' => $formOne->createView(),
            'candidat' => $candidat,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }

    #[Route('/centre-de-formation', name: 'app_v2_candidate_dashboard_formation')]
    public function formation(PlaylistRepository $playlistRepository, VideoRepository $videoRepository): Response
    {
        return $this->render('v2/dashboard/candidate/formation.html.twig', [
            'playlists' => $playlistRepository->findAll(),
            'videos' => $videoRepository->findAll(),
        ]);
    }

    #[Route('/centre-de-formation/playlist/{id}', name: 'app_v2_candidate_dashboard_formation_playlist_view')]
    public function viewPlaylist(Playlist $playlist): Response
    {
        return $this->render('v2/dashboard/candidate/_playlist.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/outils-ai', name: 'app_v2_candidate_dashboard_ai_tools')]
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
        return $this->render('v2/dashboard/candidate/ai_tools.html.twig', [
            'aiTools' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/boost-profile', name: 'app_v2_candidate_boost_profile', methods: ['POST'])]
    public function boostProfile(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement.');
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $candidat = $this->userService->checkProfile();
        $form = $this->createForm(CandidateBoostType::class, $candidat); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $boostOptionFacebook = $form->get('boostFacebook')->getData(); 
            $candidat = $form->getData();
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
                    $resultProfile = $this->handleBoostProfile($boostOption, $candidat, $currentUser);
                    $resultFacebook = $this->handleBoostFacebook($boostOptionFacebook, $candidat, $currentUser);

                    $result['message'] = $resultProfile['message'] . ' ' . $resultFacebook['message'];
                    $result['success'] = $resultProfile['success'] && $resultFacebook['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fs-6 fw-bold text-uppercase"><i class="bi bi-facebook me-2"></i> Boost facebook</span><br><span class="small fw-light"> Jusqu\'au '.$resultFacebook['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                        <div class="text-center"><span class="fs-6 fw-bold text-uppercase"><i class="bi bi-rocket me-2"></i> Profil boosté</span><br><span class="small fw-light"> Jusqu\'au '.$resultProfile['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                    ';
                } else {
                    $resultProfile = $this->handleBoostProfile($boostOption, $candidat, $currentUser);
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

    private function handleBoostProfile($boostOption, $candidat, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(Boost::class)->findAll();
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($candidat->getCandidat(), $boostOption->getCredit());
        
        if (isset($response['success'])) {
            $candidat->setStatus(CandidateProfile::STATUS_FEATURED);
            $candidat->setBoost($boostOption);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($candidat);
            $this->em->persist($currentUser);
            $this->em->flush();
            return [
                'message' => 'Votre profil est maintenant boosté.',
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

    private function handleBoostFacebook($boostOptionFacebook, $candidat, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
            ->findBoostVisibilityByBoostFacebookAndCandidate($boostOptionFacebook, $currentUser);

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit());

        if (isset($response['success'])) {
            $candidat->setStatus(CandidateProfile::STATUS_FEATURED);
            $candidat->setBoostFacebook($boostOptionFacebook);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($candidat);
            $this->em->persist($currentUser);
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
