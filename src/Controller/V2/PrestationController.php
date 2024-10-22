<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Service\FileUploader;
use App\Data\V2\PrestationData;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Twig\PrestationExtension;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\Vues\PrestationVues;
use App\Entity\BusinessModel\Credit;
use App\Security\Voter\PrestationVoter;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use App\Form\PrestationBoostType;
use App\Manager\BusinessModel\BoostVisibilityManager;
use App\Manager\MailManager;
use App\Twig\AppExtension;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/v2/dashboard')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private AppExtension $appExtension,
        private UserService $userService,
        private PrestationManager $prestationManager,
        private PrestationExtension $prestationExtension,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private CreditManager $creditManager,
        private MailManager $mailManager,
        private BoostVisibilityManager $boostVisibilityManager,
    ){}
    
    #[Route('/prestations', name: 'app_v2_prestation')]
    public function index(Request $request): Response
    {
        $data = new PrestationData();
        $data->page = $request->get('page', 1);
        $profile = $this->userService->checkProfile();
        if($profile instanceof CandidateProfile){
            $data->candidat = $profile;
        }
        if($profile instanceof EntrepriseProfile){
            $data->entreprise = $profile;
        }

        $secteurs = $profile->getSecteurs();
        $page = $request->query->get('page', 1);
        $limit = 10;
        $qb = $this->em->getRepository(Prestation::class)->createQueryBuilder('p');

        $qb->join('p.secteurs', 's') 
        ->where('p.status = :valid OR p.status = :featured') 
        ->setParameter('valid', Prestation::STATUS_VALID)
        ->setParameter('featured', Prestation::STATUS_FEATURED)
        // ->andWhere('s IN (:secteurs)') 
        // ->setParameter('secteurs', $secteurs)
        ->orderBy('p.id', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult(($page - 1) * $limit);

        $prestations = $qb->getQuery()->getResult();

        return $this->render('v2/dashboard/prestation/index.html.twig', [
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_prestations'),
            'prestations' => $prestations,
            'profile' => $profile
        ]);
    }
    
    #[Route('/api/prestations', name: 'app_v2_prestation_scroll')]
    public function scroll(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $profile = $this->userService->checkProfile(); 
        $secteurs = $profile->getSecteurs();
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $qb = $this->em->getRepository(Prestation::class)->createQueryBuilder('p');

        $qb->join('p.secteurs', 's') 
        ->where('p.status = :valid OR p.status = :featured') 
        ->setParameter('valid', Prestation::STATUS_VALID)
        ->setParameter('featured', Prestation::STATUS_FEATURED)
        // ->andWhere('s IN (:secteurs)') 
        // ->setParameter('secteurs', $secteurs)
        ->orderBy('p.id', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult(($page - 1) * $limit);

        $prestations = $qb->getQuery()->getResult();

        return $this->render('v2/dashboard/prestation/_prestations_list.html.twig', [
            'prestations' => $prestations,
            'profile' => $profile,
        ]);
    }
    
    #[Route('/prestation/my-created', name: 'app_v2_prestation_my_created')]
    public function myCreated(Request $request): Response
    {
        $data = new PrestationData();
        $data->page = $request->get('page', 1);
        $profile = $this->userService->checkProfile();
        if($profile instanceof CandidateProfile){
            $data->candidat = $profile;
        }
        if($profile instanceof EntrepriseProfile){
            $data->entreprise = $profile;
        }

        return $this->render('v2/dashboard/prestation/my_created.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findSearch($data),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_prestations'),
        ]);
    }
    
    #[Route('/prestation/create', name: 'app_v2_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setContactEmail($currentUser->getEmail());
        $prestation->setContactTelephone($currentUser->getTelephone());
        $profile = $this->userService->checkProfile();
        if($profile instanceof CandidateProfile){
            $prestation->setCandidateProfile($profile);
            $boostType = 'PRESTATION_CANDIDATE';
        }
        if($profile instanceof EntrepriseProfile){
            $prestation->setEntrepriseProfile($profile);
            $boostType = 'PRESTATION_RECRUITER';
        }
        $boostType = 'PRESTATION_CANDIDATE';
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => $boostType]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $response = $this->handlePrestationSubmission($form->getData(), $currentUser);
            if ($response['success']) {
                $boostOption = $form->get('boost')->getData();
                $boostFacebookOption = $form->get('boostFacebook')->getData();
                $prestation = $form->getData();
                if($boostOption instanceof Boost){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                    $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                    $prestation->setStatus(Prestation::STATUS_FEATURED);
                    $prestation->setBoost($boostOption);
                    $prestation->addBoostVisibility($visibilityBoost);
                    $currentUser->addBoostVisibility($visibilityBoost);
                    $this->em->persist($currentUser);
                    $this->em->persist($visibilityBoost);
                    $this->em->flush();
                }
                if($boostFacebookOption instanceof BoostFacebook){
                    $visibilityBoostFacebook = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostFacebookOption);
                    $visibilityBoostFacebook = $this->boostVisibilityManager->updateFacebook($visibilityBoostFacebook, $boostFacebookOption);
                    $prestation->setStatus(Prestation::STATUS_FEATURED);
                    $prestation->setBoostFacebook($boostFacebookOption);
                    $prestation->addBoostVisibility($visibilityBoostFacebook);
                    $currentUser->addBoostVisibility($visibilityBoostFacebook);
                    $this->em->persist($currentUser);
                    $this->em->persist($visibilityBoostFacebook);
                    $this->em->flush();
                    $this->mailManager->facebookBoostPrestation($currentUser, $prestation, $visibilityBoostFacebook);
                }
                
                $this->prestationManager->saveForm($form);
                $response['redirect'] = $this->urlGeneratorInterface->generate('app_v2_view_prestation', ['prestation' => $prestation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                return $this->json($response, 200);
            } 
            return $this->json($response, 200);
        }else {
            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/prestation/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('v2/dashboard/prestation/create.html.twig', [
            'form' => $form->createView(),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_prestations'),
        ]);
    }
    
    private function handlePrestationSubmission(Prestation $prestation, User $currentUser): array
    {
        $boost = $prestation->getBoost();
        $boostFacebook = $prestation->getBoostFacebook();
        
        // Initialisation par défaut pour éviter les "undefined keys"
        $responseBoost = ['success' => true];
        $responseBoostFacebook = ['success' => true];
        $responseDefault = ['success' => true];
        
        $hasBoost = $boost instanceof Boost;
        $hasBoostFacebook = $boostFacebook instanceof BoostFacebook;

        // Vérification et ajustement des crédits pour la prestation standard
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
        if ($this->profileManager->canBuy($currentUser, $creditAmount)) {
            $responseDefault = $this->creditManager->adjustCredits($currentUser, $creditAmount);
        } else {
            return [
                'success' => false, 
                'status' => 'Echec',
                'message' => "Crédits insuffisants pour publier une prestation"
            ];
        }

        // Vérification et ajustement des crédits pour le Boost standard
        if ($hasBoost) {
            if ($this->profileManager->canBuy($currentUser, $boost->getCredit())) {
                $responseBoost = $this->creditManager->adjustCredits($currentUser, $boost->getCredit());
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
                $responseBoostFacebook = $this->creditManager->adjustCredits($currentUser, $boostFacebook->getCredit());
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
    
    #[Route('/prestation/edit/{prestation}', name: 'app_v2_edit_prestation')]
    #[IsGranted(PrestationVoter::EDIT, subject: 'prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => 'PRESTATION_RECRUITER']);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $response = $this->handlePrestationEdit($form->getData(), $currentUser);
            
            if ($response['success']) {
                $this->prestationManager->saveForm($form);
            }
            
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
            }
            
            return $this->render('v2/dashboard/prestation/edit.html.twig', [
                'prestation' => $prestation,
                'form' => $form->createView(),
                'message' => $response['message'],
                'status' => $response['status'],
            ]);
        }

        return $this->render('v2/dashboard/prestation/edit.html.twig', [
            'prestation' => $prestation,
            'form' => $form->createView(),
        ]);
    }

    private function handlePrestationEdit(Prestation $prestation, User $currentUser): array
    {
        $boost = $prestation->getBoost();
        if ($boost instanceof Boost) {
            if($this->profileManager->canBuy($currentUser, $boost->getCredit())){
                $response = $this->creditManager->adjustCredits($currentUser, $boost->getCredit());
            }else{
                return ['success' => false, 'message' => 'Crédits insuffisants. Veuillez charger votre compte', 'status' => '<i class="bi bi-exclamation-octagon me-2"></i> Echec'];
            }
            if (!empty($response['error'])) {
                return ['success' => false, 'message' => $response['error'], 'status' => '<i class="bi bi-exclamation-octagon me-2"></i> Echec'];
            }
        }

        return ['success' => true, 'message' => 'Modification sauvegardée avec succès', 'status' => '<i class="bi bi-check-lg me-2"></i> Succès'];
    }
    
    #[Route('/prestation/view/{prestation}', name: 'app_v2_view_prestation')]
    #[IsGranted(PrestationVoter::VIEW, subject: 'prestation')]
    public function viewPrestation(Request $request, Prestation $prestation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $ipAddress = $request->getClientIp();
        $viewRepository = $this->em->getRepository(PrestationVues::class);
        $existingView = $viewRepository->findOneBy([
            'prestation' => $prestation,
            'ipAddress' => $ipAddress,
        ]);

        if (!$existingView) {
            $view = new PrestationVues();
            $view->setPrestation($prestation);
            $view->setIpAddress($ipAddress);
            $view->setCreatedAt(new \DateTime());

            $this->em->persist($view);
            $prestation->addPrestationVue($view);
            $this->em->flush();
        }

        $owner = false;
        $creater = $this->prestationExtension->getUserPrestation($prestation);
        if($creater == $currentUser){
            $owner = true;
        }
        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $creater,
        ]);

        return $this->render('v2/dashboard/prestation/view.html.twig', [
            'prestation' => $prestation,
            'purchasedContact' => $purchasedContact,
            'creater' => $creater,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_prestations'),
            'owner' => $owner,
        ]);
    }
    
    #[Route('/prestation/delete', name: 'app_v2_delete_prestation', methods: ['POST'])]
    #[IsGranted(PrestationVoter::EDIT, subject: 'prestation')]
    public function removePrestation(Request $request): Response
    {
        $prestationId = $request->request->get('prestationId');
        $prestation = $this->em->getRepository(Prestation::class)->find($prestationId);
        $message = "La prestation a bien été supprimée";
        $prestation->setStatus(Prestation::STATUS_DELETED);
        $this->em->persist($prestation);
        $this->em->flush();

        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/prestation/delete.html.twig', [
                'prestationId' => $prestationId,
                'message' => $message,
            ]);
        }

        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_prestation');

    }

    #[Route('/boost-prestation', name: 'app_v2_boost_prestation', methods: ['POST'])]
    public function boostPrestation(Request $request): Response
    {
        $id = $request->request->getInt('id', 0);
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var Prestation|null $prestation */
        $prestation = $this->em->getRepository(Prestation::class)->find($id);
        if(!$prestation){
            return $this->json([
                'message' => 'Prestation invalid'
            ], 400);
        }
        if($prestation->getCandidateProfile() instanceof CandidateProfile){
            $profile = $prestation->getCandidateProfile();
        }
        if($prestation->getEntrepriseProfile() instanceof EntrepriseProfile){
            $profile = $prestation->getEntrepriseProfile();
        }
        $form = $this->createForm(PrestationBoostType::class, $prestation, ['boostType' => 'PRESTATION_CANDIDATE']); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $boostOptionFacebook = $form->get('boostFacebook')->getData(); 
            $prestation = $form->getData();
            if ($boostOption === 0) {
                $boostOption = null; // Rendre null si la valeur est 0
            }
            if ($boostOptionFacebook === 0) {
                $boostOptionFacebook = null; // Rendre null si la valeur est 0
            }

            $result = [
                'id' => $prestation->getId(),
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
                    $resultProfile = $this->handleBoostPrestation($boostOption, $prestation, $currentUser);
                    $resultFacebook = $this->handleBoostFacebook($boostOptionFacebook, $prestation, $currentUser);

                    $result['message'] = $resultProfile['message'] . ' ' . $resultFacebook['message'];
                    $result['success'] = $resultProfile['success'] && $resultFacebook['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fw-semibold small">Boost '.$resultProfile['visibilityBoost']->getDurationDays().' jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($resultProfile['visibilityBoost']->getEndDate()).'</span></div>
                        <div class="text-center"><span class="small fw-semibold"><i class="bi bi-facebook me-2"></i> Boost</span><br><span class="small fw-light"> Jusqu\'au '.$resultFacebook['visibilityBoost']->getEndDate()->format('d-m-Y \à H:i').' </span></div>
                    ';
                } else {
                    $resultProfile = $this->handleBoostPrestation($boostOption, $prestation, $currentUser);
                    $result['message'] = $resultProfile['message'];
                    $result['success'] = $resultProfile['success'];
                    $result['detail'] = '
                        <div class="text-center"><span class="fw-semibold small">Boost '.$resultProfile['visibilityBoost']->getDurationDays().' jour</span><br><span class="fw-lighter small"> Expire '.$this->appExtension->timeUntil($resultProfile['visibilityBoost']->getEndDate()).'</span></div>
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
            'success' => false, 
            'message' => 'Erreur de formulaire PrestationBoostType.'
        ], 400);
    }

    private function handleBoostPrestation($boostOption, $prestation, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)->findBoostVisibilityByBoostAndPrestation($boostOption, $prestation);
        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        }
        $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOption->getCredit());
        
        if (isset($response['success'])) {
            $prestation->setStatus(Prestation::STATUS_FEATURED);
            $prestation->setBoost($boostOption);
            $visibilityBoost->setPrestation($prestation);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($prestation);
            $this->em->persist($currentUser);
            $this->em->flush();
            return [
                'message' => 'Votre prestation est maintenant boostée.',
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

    private function handleBoostFacebook($boostOptionFacebook, $prestation, User $currentUser): array
    {
        $visibilityBoost = $this->em->getRepository(BoostVisibility::class)
            ->findBoostVisibilityByBoostFacebookAndPrestation($boostOptionFacebook, $prestation);

        if (!$visibilityBoost instanceof BoostVisibility) {
            $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        }
        $visibilityBoost = $this->boostVisibilityManager->updateFacebook($visibilityBoost, $boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit());

        if (isset($response['success'])) {
            $prestation->setStatus(CandidateProfile::STATUS_FEATURED);
            $prestation->setBoostFacebook($boostOptionFacebook);
            $visibilityBoost->setPrestation($prestation);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($prestation);
            $this->em->persist($currentUser);
            $this->em->flush();
            $this->mailManager->facebookBoostPrestation($currentUser, $prestation, $visibilityBoost);

            return [
                'message' => 'Votre prestation est maintenant boosté sur facebook',
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

    private function handleNewBoostPrestation($boostOption, $prestation, User $currentUser): array
    {
        $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOption->getCredit());
        
        if (isset($response['success'])) {
            $prestation->setStatus(Prestation::STATUS_FEATURED);
            $prestation->setBoost($boostOption);
            $visibilityBoost->setPrestation($prestation);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($prestation);
            $this->em->persist($currentUser);
            $this->em->flush();
            return [
                'message' => 'Votre prestation est maintenant boostée.',
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

    private function handleNewBoostFacebook($boostOptionFacebook, $prestation, User $currentUser): array
    {
        $visibilityBoost = $this->boostVisibilityManager->initBoostvisibilityFacebook($boostOptionFacebook);
        $response = $this->creditManager->adjustCredits($currentUser, $boostOptionFacebook->getCredit());

        if (isset($response['success'])) {
            $prestation->setStatus(CandidateProfile::STATUS_FEATURED);
            $prestation->setBoostFacebook($boostOptionFacebook);
            $visibilityBoost->setPrestation($prestation);
            $currentUser->addBoostVisibility($visibilityBoost);
            $this->em->persist($prestation);
            $this->em->persist($currentUser);
            $this->em->flush();
            $this->mailManager->facebookBoostPrestation($currentUser, $prestation, $visibilityBoost);

            return [
                'message' => 'Votre prestation est maintenant boosté sur facebook',
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
