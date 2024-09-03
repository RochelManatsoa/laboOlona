<?php

namespace App\Controller\V2\Recruiter;

use App\Manager\ProfileManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Formation\Playlist;
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
            $recruiter = $form->getData();
            $message = 'Erreur sur le formulaire';
            $success = false;
            $status = 'Echec';

            if ($this->profileManager->canApplyBoost($recruiter->getEntreprise(), $boostOption)) {
                $visibilityBoost = $recruiter->getBoostVisibility();
                if(!$visibilityBoost instanceof BoostVisibility){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                }
                $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                $response = $this->creditManager->adjustCredits($recruiter->getEntreprise(), $boostOption->getCredit());

                if(isset($response['success'])){
                    $recruiter->setBoostVisibility($visibilityBoost);
                    $recruiter->setStatus(EntrepriseProfile::STATUS_PREMIUM);
                    $this->em->persist($recruiter);
                    $this->em->flush();
                    $message = 'Votre entreprise est maintenant boosté';
                    $success = true;
                    $status = 'Succès';
                }else{
                    $message = 'Une erreur s\'est produite.';
                    $success = false;
                    $status = 'Echec';
                }
            } else {
                $message = 'Crédits insuffisants pour ce boost.';
                $success = false;
                $status = 'Echec';
            }
        }

        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/dashboard/candidate/update.html.twig', [
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'visibilityBoost' => $visibilityBoost,
                'credit' => $currentUser->getCredit()->getTotal(),
            ]);
        }

        return $this->json([
            'message' => $message,
            'success' => $success,
            'status' => $status,
            'credit' => $currentUser->getCredit()->getTotal(),
        ], 200);        
    }
}
