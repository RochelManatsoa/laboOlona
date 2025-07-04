<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Manager\MailManager;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Credit;
use App\Manager\AffiliateToolManager;
use App\Form\Boost\CandidateBoostType;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        private MailManager $mailManager,
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

        return $this->redirectToRoute('app_tableau_de_bord_candidat');

        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        $creditAmount = 0;
        if($candidat->isIsGeneretated()){
            $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_UPLOAD_CV);
        }
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
                    $response = $this->creditManager->adjustCredits($this->userService->getCurrentUser(), $creditAmount, "Generation de résumé CV");
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
            'creditAmount' => $creditAmount,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
            'activities' => $this->em->getRepository(ActivityLog::class)->findUserLogs($this->userService->getCurrentUser()),
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
            dd($boostOption);
            $boostOptionFacebook = $form->get('boostFacebook')->getData(); 
            $candidat = $form->getData();
            if ($boostOptionFacebook === 0) {
                $boostOptionFacebook = null; // Rendre null si la valeur est 0
            }

            // Initialiser un tableau pour le résultat
            $result = [
                'success' => false,
                'status' => 'Echec',
                'detail' => '',
                'credit' => $currentUser->getCredit()->getTotal(),
            ];

            // Vérifier si les boosts peuvent être appliqués
            if($boostOption === null){
                $canApplyBoost = false;
            }else{
                $canApplyBoost = $this->profileManager->canApplyBoost($currentUser, $boostOption);
            }
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
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
            ->findBoostVisibilityByBoostAndUser($boostOption, $currentUser, 'PROFILE_CANDIDATE');
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($candidat->getCandidat(), $boostOption->getCredit(), "Boost profil sur Olona Talents");
        
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
            ->findBoostVisibilityByBoostFacebookAndUser($boostOptionFacebook, $currentUser, 'PROFILE_CANDIDAT');

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit(), "Boost profil sur facebook");

        if (isset($response['success'])) {
            $candidat->setStatus(CandidateProfile::STATUS_FEATURED);
            $candidat->setBoostFacebook($boostOptionFacebook);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($candidat);
            $this->em->persist($currentUser);
            $this->em->flush();
            $this->mailManager->facebookBoostProfile($candidat->getCandidat(), $visibilityBoost);

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
