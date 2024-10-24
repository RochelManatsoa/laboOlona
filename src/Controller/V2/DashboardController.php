<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Form\V2\AccountType;
use App\Form\V2\ProfileType;
use App\Form\V2\CandidateType;
use App\Form\V2\RecruiterType;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Vues\CandidatVues;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\NotificationManager;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\ContactForm;
use App\Form\Moderateur\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Boost\CreateCandidateBoostType;
use App\Form\Boost\CreateRecruiterBoostType;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Manager\BusinessModel\BoostVisibilityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
        private ProfileManager $profileManager,
        private NotificationManager $notificationManager,
        private CandidatManager $candidatManager,
        private CreditManager $creditManager,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private BoostVisibilityManager $boostVisibilityManager,
    ){}

    #[Route('/', name: 'app_v2_dashboard')]
    public function index(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $profile = $this->userService->checkProfile();
        if($profile instanceof EntrepriseProfile){
            return $this->redirectToRoute('app_v2_recruiter_dashboard');
        }
        if($profile instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_candidate_dashboard');
        }
        if($profile instanceof ModerateurProfile){
            return $this->redirectToRoute('app_dashboard_moderateur');
        }

        return $this->redirectToRoute('app_v2_dashboard_create_profile', ['id' => $currentUser->getId()]);
    }
    
    #[Route('/providers/{id}/create', name: 'app_v2_dashboard_create_profile')]
    public function profileInfo(Request $request, User $user): Response
    {             
        $session = $this->requestStack->getSession();        
        $typology = $session->has('typology') && $session->get('typology') !== null ? $session->get('typology') : 'Candidat';
        $typology = ucfirst($typology); 
        $user->setType(strtoupper($typology));
        $this->userService->save($user); 
        if($user->getType() === User::ACCOUNT_ENTREPRISE || $typology === 'Entreprise'){
            $recruiter = $user->getEntrepriseProfile();
            if(!$recruiter instanceof EntrepriseProfile){
                $recruiter = $this->profileManager->createCompany($user); 
            }
            $formProfileUser = $this->createForm(RecruiterType::class, $recruiter); 
        }else{
            $candidat = $user->getCandidateProfile();
            if(!$candidat instanceof CandidateProfile){
                $candidat = $this->profileManager->createCandidat($user); 
            }
            $formProfileUser = $this->createForm(CandidateType::class, $candidat); 
        }

        $form = $this->createForm(AccountType::class, $user, ['typology' => $typology]);
        $form->handleRequest($request);
        $formProfileUser->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $success = false;
            $user = $form->getData();

            if ($user->getType() === User::ACCOUNT_CANDIDAT) {
                $user->setEntrepriseProfile(null); 
                $candidat = $this->profileManager->createCandidat($user); 
                $formProfileUser = $this->createForm(CandidateType::class, $candidat); 
            }

            if ($user->getType() === User::ACCOUNT_ENTREPRISE) {
                $user->setCandidateProfile(null); 
                $recruiter = $this->profileManager->createCompany($user); 
                $formProfileUser = $this->createForm(RecruiterType::class, $recruiter);
            }

            $this->em->persist($user);
            $this->em->flush();

            $session->set('typology', ucfirst(strtolower($user->getType())));

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('v2/dashboard/provider/update.html.twig', [
                    'formProfileUser' => $formProfileUser->createView(),
                    'success' => $success,
                ]);
            }
            $success = true;

            return $this->json([
                'message' => 'Formulaire invalide',
                'status' => 'Echec',
                'success' => $success,
            ], 200);
        }

        if($formProfileUser->isSubmitted() && $formProfileUser->isValid()){
            $profile = $formProfileUser->getData();
            $this->em->persist($profile);
            $this->em->flush();

            return $this->redirectToRoute('app_v2_dashboard_contact_profile', ['id' => $user->getId()]);
        }else{
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('v2/dashboard/provider/update.html.twig', [
                    'formProfileUser' => $formProfileUser->createView(),
                    'success' => false,
                ]);
            }
        }

        return $this->render('v2/dashboard/provider/create.html.twig', [
            'form' => $form->createView(),
            'formProfileUser' => $formProfileUser->createView(),
        ]);
    }

    #[Route('/providers/{id}/contact', name: 'app_v2_dashboard_contact_profile')]
    public function contactInfo(Request $request, User $user): Response
    {
        $form = $this->createForm(ProfileType::class, $user,[]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $candidat = $user->getCandidateProfile();
            $recruiter = $user->getEntrepriseProfile();
            if($user->getType() === User::ACCOUNT_CANDIDAT){
                $user->setEntrepriseProfile(null);
                $this->em->persist($candidat);
            }
            if($user->getType() === User::ACCOUNT_ENTREPRISE){
                $user->setCandidateProfile(null);
                $this->em->persist($recruiter);
            }
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('app_v2_dashboard_boost_profile', ['id' => $user->getId()]);
        }
        
        return $this->render('v2/dashboard/provider/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/providers/{id}/boost', name: 'app_v2_dashboard_boost_profile')]
    public function userboost(Request $request, User $user): Response
    {        
        if($user->getType() === User::ACCOUNT_CANDIDAT){
            $form = $this->createForm(CreateCandidateBoostType::class, $user->getCandidateProfile()); 
        }else{
            $form = $this->createForm(CreateRecruiterBoostType::class, $user->getEntrepriseProfile()); 
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $profile = $form->getData();
            $visibilityBoost = $profile->getBoostVisibility();

            if ($this->profileManager->canApplyBoost($user, $boostOption)) {
                if(!$visibilityBoost instanceof BoostVisibility){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                }
                $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                $response = $this->creditManager->adjustCredits($user, $boostOption->getCredit(), "Boost Profil Olona Talents");
                
                $message = 'Crédits insuffisants pour ce boost.';
                $success = true;
                $status = 'Succès';
            
                if(isset($response['success'])){
                    $profile->setBoostVisibility($visibilityBoost);
                    $profile->setStatus(CandidateProfile::STATUS_FEATURED);
                    $this->em->persist($profile);
                    $this->em->flush();
                    $message = 'Votre profil est maintenant boosté';
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

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/provider/update.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'visibilityBoost' => $visibilityBoost,
                    'credit' => $user->getCredit()->getTotal(),
                ]);
            }

            return $this->json([
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'credit' => $user->getCredit()->getTotal(),
            ], 200);
        }
        
        return $this->render('v2/dashboard/provider/boost.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/view/{id}', name: 'app_v2_profile_view')]
    public function viewProfile(Request $request, int $id): Response
    {
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $profile = $this->userService->checkProfile();
        if(!$profile instanceof EntrepriseProfile){
            $profile = null;
        }

        $ipAddress = $request->getClientIp();
        $viewRepository = $this->em->getRepository(CandidatVues::class);
        $existingView = $viewRepository->findOneBy([
            'candidat' => $candidat,
            'ipAddress' => $ipAddress,
        ]);

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $candidat->getCandidat(),
        ]);

        if (!$existingView) {
            $view = new CandidatVues();
            $view->setCandidat($candidat);
            $view->setIpAddress($ipAddress);
            $view->setCreatedAt(new \DateTime());

            $this->em->persist($view);
            $candidat->addVue($view);
            $this->em->flush();
        }
        
        return $this->render('v2/dashboard/recruiter/profile/view.html.twig', [
            'candidat' => $candidat,
            'type' => $currentUser->getType(),
            'recruiter' => $profile,
            'purchasedContact' => $purchasedContact,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }

    #[Route('/contact', name: 'app_v2_contact')]
    public function support(Request $request): Response
    {
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $this->em->persist($contactForm);
            $this->em->flush();
            $this->mailerService->sendMultiple(
                ["contact@olona-talents.com", "nirinarocheldev@gmail.com", "techniques@olona-talents.com"],
                "Nouvelle entrée sur le formulaire de contact",
                "contact.html.twig",
                [
                    'user' => $contactForm,
                ]
            );
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');
        }

        return $this->render('v2/dashboard/support.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
