<?php

namespace App\Controller\V2\Candidate;

use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Formation\Playlist;
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

        $formOne = $this->createForm(EditStepOneType::class, $candidat);
        $formOne->handleRequest($request);

        $form = $this->createForm(CandidateUploadType::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $cvFile = $form->get('cv')->getData();
            $this->em->persist($candidat);
            $this->em->flush();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $candidat);
            }

            $response = $this->candidatController->analyse(new \Symfony\Component\HttpFoundation\Request(), $candidat);
            $content = $response->getContent(); 
            $data = json_decode($content, true);
            if ($data['status'] == 'success') {
                $this->addFlash('success', 'Analyse effectué avec succès');
            } else {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\'analyse de votre cv');
            }
            
            return $this->redirectToRoute('app_v2_dashboard');
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

        $candidat = $this->userService->checkProfile();
        $form = $this->createForm(CandidateBoostType::class, $candidat); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 

            if ($this->profileManager->canApplyBoost($candidat->getCandidat(), $boostOption)) {
                $visibilityBoost = $candidat->getBoostVisibility();
                if(!$visibilityBoost instanceof BoostVisibility){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                }
                $candidat->setBoostVisibility($visibilityBoost);
                $response = $this->creditManager->adjustCredits($candidat->getCandidat(), $boostOption->getCredit());
                if(isset($response['success'])){
                    $this->em->persist($candidat);
                    $this->em->flush();
                    return $this->json(['status' => 'success'], 200);
                }else{
                    return $this->json(['status' => 'error', 'message' => 'Une erreur s\'est produite.'], 400);
                }
            } else {
                return $this->json(['status' => 'error', 'message' => 'Crédits insuffisants pour ce boost.'], 400);
            }
        }

        return $this->json(['status' => 'error', 'message' => 'Erreur de formulaire.'], 400);
    }

}
