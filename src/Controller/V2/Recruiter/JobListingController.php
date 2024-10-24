<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\EntrepriseManager;
use App\Manager\JobListingManager;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use App\Form\Entreprise\AnnonceType;
use App\Entity\Entreprise\JobListing;
use App\Security\Voter\JobListingVoter;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Entreprise\AnnonceBoostType;
use App\Entity\BusinessModel\BoostFacebook;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\BoostVisibilityManager;
use App\Manager\MailManager;
use App\Twig\AppExtension;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
class JobListingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private JobListingManager $jobListingManager,
        private EntrepriseManager $entrepriseManager,
        private PaginatorInterface $paginator,
        private BoostVisibilityManager $boostVisibilityManager,
        private CreditManager $creditManager,
        private ProfileManager $profileManager,
        private MailManager $mailManager,
        private AppExtension $appExtension,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    #[Route('/job-listings', name: 'app_v2_recruiter_job_listing')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $jobListings = $this->em->getRepository(JobListing::class)->findJobListingsByEntreprise($recruiter);

        return $this->render('v2/dashboard/recruiter/job_listing/index.html.twig', [
            'joblistings' => $this->paginator->paginate(
                $jobListings,
                $request->query->getInt('page', 1),
                20
            ),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
        ]);
    }

    #[Route('/job-listing/create', name: 'app_v2_recruiter_create_job_listing')]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Accès refusé. Cette section est réservée aux recruteurs.');
        /** @var EntrepriseProfile $recruiter */
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $jobListing = $this->jobListingManager->init();
        $devise = $this->entrepriseManager->getEntrepriseDevise($recruiter);
        $budget = $this->jobListingManager->initBudgetAnnonce();
        $budget->setCurrency($devise);
        $jobListing->setEntreprise($recruiter);
        $jobListing->setBudgetAnnonce($budget);
    
        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->handleJobListingSubmission($form->getData(), $currentUser);
            
            if ($response['success']) {
                $boostOption = $form->get('boost')->getData(); 
                $boostFacebookOption = $form->get('boostFacebook')->getData();
                $jobListing = $form->getData();
                if($boostOption instanceof Boost){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                    $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                    $jobListing->setStatus(JobListing::STATUS_FEATURED);
                    $jobListing->setBoost($boostOption);
                    $jobListing->addBoostVisibility($visibilityBoost);
                    $currentUser->addBoostVisibility($visibilityBoost);
                    $this->em->persist($visibilityBoost);
                    $this->em->flush();
                }
                if($boostFacebookOption instanceof BoostFacebook){
                    $visibilityBoostFacebook = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostFacebookOption);
                    $visibilityBoostFacebook = $this->boostVisibilityManager->updateFacebook($visibilityBoostFacebook, $boostFacebookOption);
                    $jobListing->setStatus(JobListing::STATUS_FEATURED);
                    $jobListing->setBoostFacebook($boostFacebookOption);
                    $jobListing->addBoostVisibility($visibilityBoostFacebook);
                    $currentUser->addBoostVisibility($visibilityBoostFacebook);
                    $this->em->persist($currentUser);
                    $this->em->persist($visibilityBoostFacebook);
                    $this->em->flush();
                    $this->mailManager->facebookBoostJobListing($currentUser, $jobListing, $visibilityBoostFacebook);
                }
                $this->jobListingManager->saveForm($form);
                return $this->redirectToRoute('app_v2_recruiter_job_listing_view', ['jobListing' => $jobListing->getId()]);
            } else {
                if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                    return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
                }
            }
        }else{
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('v2/dashboard/recruiter/update.html.twig', [
                    'form' => $form->createView(),
                    'success' => false,
                ]);
            }
        }
    
        return $this->render('v2/dashboard/recruiter/job_listing/create.html.twig', [
            'form' => $form->createView(),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
        ]);
    }
    
    private function handleJobListingSubmission(JobListing $jobListing, User $currentUser): array
    {
        $boost = $jobListing->getBoost();
        $boostFacebook = $jobListing->getBoostFacebook();
        
        // Initialisation par défaut pour éviter les "undefined keys"
        $responseBoost = ['success' => true];
        $responseBoostFacebook = ['success' => true];
        $responseDefault = ['success' => true];
        
        $hasBoost = $boost instanceof Boost;
        $hasBoostFacebook = $boostFacebook instanceof BoostFacebook;
        
        // Vérification et ajustement des crédits pour l'annonce standard
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_OFFER);
        if ($this->profileManager->canBuy($currentUser, $creditAmount)) {
            $responseDefault = $this->creditManager->adjustCredits($currentUser, $creditAmount, "Publication annonce");
        } else {
            return [
                'success' => false, 
                'status' => 'Echec',
                'message' => "Crédits insuffisants pour publier une annonce"
            ];
        }

        // Vérification et ajustement des crédits pour le Boost standard
        if ($hasBoost) {
            if ($this->profileManager->canBuy($currentUser, $boost->getCredit())) {
                $responseBoost = $this->creditManager->adjustCredits($currentUser, $boost->getCredit(), "Boost annonce sur Olona Talents");
            } else {
                return [
                    'success' => false, 
                    'status' => 'Echec',
                    'message' => "Crédits insuffisants pour ce boost"
                ];
            }
        }

        // Vérification et ajustement des crédits pour le Boost Facebook
        if ($hasBoostFacebook) {
            if ($this->profileManager->canBuy($currentUser, $boostFacebook->getCredit())) {
                $responseBoostFacebook = $this->creditManager->adjustCredits($currentUser, $boostFacebook->getCredit(), "Boost annonce sur facebook");
            } else {
                return [
                    'success' => false, 
                    'status' => 'Echec',
                    'message' => "Crédits insuffisants pour le boost Facebook"
                ];
            }
        }

        // Si tous les ajustements de crédits ont réussi, ou si aucun boost n'a été pris, on valide l'opération
        if ($responseBoost['success'] && $responseBoostFacebook['success'] && $responseDefault['success']) {
            return [
                'success' => true, 
                'status' => 'Succès',
                'message' => "Boost effectué"
            ];
        }

        // Cas de crédits insuffisants non gérés spécifiquement
        return [
            'success' => false,
            'status' => 'Echec',
            'message' => "Crédits insuffisants pour les opérations demandées"
        ];
    }
    
    #[Route('/job-listing/edit/{jobListing}', name: 'app_v2_recruiter_job_listing_edit')]
    #[IsGranted(JobListingVoter::EDIT, subject: 'jobListing')]
    public function editJobListing(Request $request, JobListing $jobListing): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Accès refusé. Section réservée aux recruteurs.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->handleJobListingEdit($jobListing, $currentUser);
            
            if ($response['success']) {
                $boostOption = $form->get('boost')->getData(); 
                $jobListing = $form->getData();
                $visibilityBoost = $jobListing->getBoostVisibility();
                if($boostOption instanceof Boost){
                    if(!$visibilityBoost instanceof BoostVisibility){
                        $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                    }
                    $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                    $jobListing->setStatus(JobListing::STATUS_FEATURED);
                    $this->em->persist($visibilityBoost);
                    $this->em->flush();
                }
                $this->jobListingManager->saveForm($form);
            }
            
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
            }
            
            return $this->render('v2/dashboard/recruiter/job_listing/edit.html.twig', [
                'jobListing' => $jobListing,
                'form' => $form->createView(),
                'message' => $response['message'],
                'status' => $response['status'],
            ]);
        }

        return $this->render('v2/dashboard/recruiter/job_listing/edit.html.twig', [
            'jobListing' => $jobListing,
            'form' => $form->createView(),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
        ]);
    }

    private function handleJobListingEdit(JobListing $jobListing, User $currentUser): array
    {
        $boost = $jobListing->getBoost();
        if ($boost instanceof Boost) {
            $response = $this->creditManager->adjustCredits($currentUser, $boost->getCredit(), "Boost annonce sur Olona Talents");
            if (!empty($response['error'])) {
                return ['success' => false, 'message' => $response['error'], 'status' => 'Echec'];
            }
        }

        return ['success' => true, 'message' => 'Modification sauvegardée avec succès', 'status' => 'Succès'];
    }

    
    #[Route('/job-listing/view/{jobListing}', name: 'app_v2_recruiter_job_listing_view')]
    #[IsGranted(JobListingVoter::VIEW, subject: 'jobListing')]
    public function viewJobListing(Request $request, JobListing $jobListing): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/job_listing/view.html.twig', [
            'annonce' => $jobListing,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
        ]);
    }
    
    #[Route('/job-listing/delete', name: 'app_v2_recruiter_delete_job_listing', methods: ['POST'])]
    public function removeJobListing(Request $request): Response
    {
        $jobListingId = $request->request->get('jobListingId');
        $jobListing = $this->em->getRepository(JobListing::class)->find($jobListingId);
        $message = "L'annonce a bien été supprimée";
        $jobListing->setStatus(JobListing::STATUS_DELETED);
        $this->em->persist($jobListing);
        $this->em->flush();
        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/job_listing/delete.html.twig', [
                'jobListingId' => $jobListingId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_job_listing');

    }

    #[Route('/boost-job-offer', name: 'app_v2_boost_job_offer', methods: ['POST'])]
    public function boostJobOffer(Request $request): Response
    {
        $id = $request->request->getInt('id', 0);
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var JobListing|null $jobListing */
        $jobListing = $this->em->getRepository(JobListing::class)->find($id);
        if(!$jobListing){
            return $this->json([
                'message' => 'JobListing invalid'
            ], 400);
        }
        $form = $this->createForm(AnnonceBoostType::class, $jobListing); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $boostOptionFacebook = $form->get('boostFacebook')->getData(); 
            $jobListing = $form->getData();
            if ($boostOption === 0) {
                $boostOption = null; // Rendre null si la valeur est 0
            }
            if ($boostOptionFacebook === 0) {
                $boostOptionFacebook = null; // Rendre null si la valeur est 0
            }

            $result = [
                'id' => $jobListing->getId(),
                'success' => false,
                'status' => 'Echec',
                'detail' => '',
                'credit' => $currentUser->getCredit()->getTotal(),
            ];

            // Vérifier si les deux boosts peuvent être appliqués
            $canApplyBoost = $this->profileManager->canApplyBoost($currentUser, $boostOption);
            $canApplyBoostFacebook = $this->profileManager->canApplyBoostFacebook($currentUser, $boostOptionFacebook);
            
            // Vérification des crédits
            if ($boostOptionFacebook && !($canApplyBoost && $canApplyBoostFacebook)) {
                // Si le boost Facebook est demandé mais crédits insuffisants pour les deux
                $result['message'] = 'Crédits insuffisants pour appliquer les deux boosts.';
            } elseif ($canApplyBoost) {
                if ($boostOptionFacebook && $canApplyBoostFacebook) {
                    $resultProfile = $this->handleBoostJobListing($boostOption, $jobListing, $currentUser);
                    $resultFacebook = $this->handleBoostFacebook($boostOptionFacebook, $jobListing, $currentUser);

                    $result['message'] = $resultProfile['message'] . ' ' . $resultFacebook['message'];
                    $result['success'] = $resultProfile['success'] && $resultFacebook['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fw-semibold small">Boost '.$resultProfile['visibilityBoost']->getDurationDays().' jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($resultProfile['visibilityBoost']->getEndDate()).'</span></div>
                        <div class="text-center"><span class="small fw-semibold"><i class="bi bi-facebook me-2"></i> Boost</span><br><span class="small fw-light"> Jusqu\'au '.$resultFacebook['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                    ';
                } else {
                    $resultProfile = $this->handleBoostJobListing($boostOption, $jobListing, $currentUser);
                    $result['message'] = $resultProfile['message'];
                    $result['success'] = $resultProfile['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fw-semibold small">Boost '.$resultProfile['visibilityBoost']->getDurationDays().' jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($resultProfile['visibilityBoost']->getEndDate()).'</span></div>
                    ';
                }
                $result['status'] = $result['success'] ? 'Succès' : 'Echec';
            } else {
                $result['message'] = 'Crédits insuffisants pour le boost annonce.';
            }

            return $this->json($result, 200);
        }

        return $this->json([
            'status' => 'error', 
            'success' => false, 
            'message' => 'Erreur de formulaire AnnonceBoostType.'
        ], 400);
    }

    private function handleBoostJobListing($boostOption, $jobListing, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)->findBoostVisibilityByBoostAndJobLisiting($boostOption, $jobListing);
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOption->getCredit(), "Boost annonce sur Olona Talents");
        
        if (isset($response['success'])) {
            $jobListing->setStatus(JobListing::STATUS_FEATURED);
            $jobListing->setBoost($boostOption);
            $visibilityBoost->setJobListing($jobListing);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($jobListing);
            $this->em->persist($currentUser);
            $this->em->flush();
            return [
                'message' => 'Votre annonce est maintenant boostée.',
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

    private function handleBoostFacebook($boostOptionFacebook, $jobListing, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
            ->findBoostVisibilityByBoostFacebookAndJobListing($boostOptionFacebook, $jobListing);

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit(), "Boost annonce sur facebook");

        if (isset($response['success'])) {
            $jobListing->setStatus(JobListing::STATUS_FEATURED);
            $jobListing->setBoostFacebook($boostOptionFacebook);
            $visibilityBoost->setJobListing($jobListing);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($jobListing);
            $this->em->persist($currentUser);
            $this->em->flush();
            $this->mailManager->facebookBoostJobListing($currentUser, $jobListing, $visibilityBoost);

            return [
                'message' => 'Votre annonce est maintenant boosté sur facebook',
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
