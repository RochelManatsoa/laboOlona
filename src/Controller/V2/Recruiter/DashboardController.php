<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Manager\AffiliateToolManager;
use App\Form\Boost\RecruiterBoostType;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\Logs\ActivityLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\BoostVisibilityManager;
use App\Manager\MailManager;
use App\Service\ActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailManager $mailManager,
        private ProfileManager $profileManager,
        private UserService $userService,
        private AffiliateToolManager $affiliateToolManager,
        private CreditManager $creditManager,
        private ActivityLogger $activityLogger,
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
            'activities' => $this->em->getRepository(ActivityLog::class)->findUserLogs($this->userService->getCurrentUser()),
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
            ->findBoostVisibilityByBoostAndUser($boostOption, $currentUser, 'PROFILE_RECRUITER');
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOption->getCredit(), "Boost profil sur Olona Talents");
        
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
            ->findBoostVisibilityByBoostFacebookAndUser($boostOptionFacebook, $currentUser, 'PROFILE_RECRUITER');

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit(), "Boost profil sur facebook");

        if (isset($response['success'])) {
            $recruiter->setStatus(EntrepriseProfile::STATUS_PREMIUM);
            $recruiter->setBoostFacebook($boostOptionFacebook);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($currentUser);
            $this->em->persist($recruiter);
            $this->em->flush();
            $this->mailManager->facebookBoostProfile($recruiter->getEntreprise(), $visibilityBoost);

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
