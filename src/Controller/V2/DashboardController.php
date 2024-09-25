<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Notification;
use App\Form\V2\ProfileType;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Vues\CandidatVues;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Credit;
use App\Manager\NotificationManager;
use App\Entity\Entreprise\JobListing;
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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        $form = $this->createForm(ProfileType::class, $user,[]);
        $form->add('type', ChoiceType::class, [
            'choices' => User::getProfileAccount(),
            'required' => true,
            'expanded' => true,
            'multiple' => false,
            'label' => false,
            'data' => User::getProfileAccount()[$typology],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            if($user->getType() === User::ACCOUNT_CANDIDAT){
                $user->setEntrepriseProfile(null);
            }
            if($user->getType() === User::ACCOUNT_ENTREPRISE){
                $user->setCandidateProfile(null);
            }
            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('app_v2_dashboard_contact_profile', ['id' => $form->getData()->getId()]);
        }else {

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/profile/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }
        
        return $this->render('v2/dashboard/profile/create.html.twig', [
            'form' => $form->createView(),
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
        
        return $this->render('v2/dashboard/profile/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/providers/{id}/boost', name: 'app_v2_dashboard_boost_profile')]
    public function userboost(Request $request, User $user): Response
    {        
        if($user->getType() === User::ACCOUNT_CANDIDAT){
            $form = $this->createForm(CreateCandidateBoostType::class, $user->getCandidateProfile()); 
        }     
        if($user->getType() === User::ACCOUNT_ENTREPRISE){
            $form = $this->createForm(CreateRecruiterBoostType::class, $user->getEntrepriseProfile()); 
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOption = $form->get('boost')->getData(); 
            $profile = $form->getData();

            if ($this->profileManager->canApplyBoost($user, $boostOption)) {
                $visibilityBoost = $profile->getBoostVisibility();
                if(!$visibilityBoost instanceof BoostVisibility){
                    $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                }
                $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                $response = $this->creditManager->adjustCredits($user, $boostOption->getCredit());
                
                $message = 'Analyse de CV effectué avec succès';
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
    
                return $this->render('v2/dashboard/candidate/update.html.twig', [
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
        
        return $this->render('v2/dashboard/profile/boost.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/contacts', name: 'app_v2_contacts')]
    public function contact(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $purchasedContacts = $this->em->getRepository(PurchasedContact::class)->findBy([
            'buyer' => $currentUser,
            'isAccepted'  => true,
        ], ['id' => 'DESC']);
        
        return $this->render('v2/dashboard/contacts/index.html.twig', [
            'contacts' => $this->paginator->paginate(
                $purchasedContacts,
                $request->query->getInt('page', 1),
                10
            )
        ]);
    }

    #[Route('/contact/view/{purchasedContact}', name: 'app_v2_contact_view')]
    public function view(Request $request, PurchasedContact $purchasedContact): Response
    {
        return $this->render('v2/dashboard/contacts/view.html.twig', [
            'contact' => $purchasedContact->getContact(),
        ]);
    }

    #[Route('/contact/delete/{contact}', name: 'app_v2_contact_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, PurchasedContact $contact): Response
    {
        $contactId = $contact->getId();
        $message = "La contact a bien été supprimée";
        $this->em->remove($contact);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/contacts/delete.html.twig', [
                'contactId' => $contactId,
                'message' => $message,
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_dashboard');
    }

    #[Route('/show-contact', name: 'app_v2_contact_show', methods: ['POST', 'GET'])]
    public function showContact(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $contactId = $request->request->get('contactId');
        $contact = $this->em->getRepository(User::class)->find($contactId);
        $message = 'Demande d\'ajout dans votre réseau professionnel envoyée';
        $success = true;
        $status = 'Succès';
    
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);
        $response = $this->creditManager->adjustCredits($currentUser, $creditAmount);
    
        $recruiter = $this->em->getRepository(EntrepriseProfile::class)->findOneBy(['entreprise' => $contactId]);
        $candidat = $this->em->getRepository(CandidateProfile::class)->findOneBy(['candidat' => $contactId]);
        
        if (isset($response['error'])) {
            $message = $response['error'];
            $success = false;
            $status = 'Echec';
        }else{
            $purchasedContact = new PurchasedContact();
            $purchasedContact->setBuyer($currentUser);
            $purchasedContact->setPurchaseDate(new \DateTime());
            $purchasedContact->setContact($contact);
            $purchasedContact->setPrice($creditAmount);
            $purchasedContact->setIsAccepted(false);
            $this->em->persist($purchasedContact);
            $this->em->flush();
            $urlAccepted = $this->urlGenerator->generate(
                'app_v2_dashboard_notification_accept',
                ['id' => $purchasedContact->getId()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $urlRefused = $this->urlGenerator->generate(
                'app_v2_dashboard_notification_refuse',
                ['id' => $purchasedContact->getId()], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $this->notificationManager->createNotification(
                $currentUser, 
                $contact, 
                Notification::TYPE_CONTACT,
                'Nouvelle demande de contact',
                ucfirst(substr($currentUser->getNom(), 0, 1)).'. '.$currentUser->getPrenom(). ' souhaite vous contacter pour une opportunité de collaboration. Acceptez-vous de partager vos coordonnées ? <br>
                <a class="btn btn-primary rounded-pill my-3 px-4" href="'.$urlAccepted.'">Accepter</a>  <a class="btn btn-danger rounded-pill my-3 px-3" href="'.$urlRefused.'">Refuser</a>
                '
            );
        }

        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/turbo/live.html.twig', [
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'recruiter' => $recruiter,
                'candidat' => $candidat,
                'user' => $contact,
                'credit' => $currentUser->getCredit()->getTotal(),
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_dashboard');
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

    #[Route('/job-offer/view/{id}', name: 'app_v2_job_offer_view')]
    public function viewJobOffer(Request $request, int $id): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $candidat = $this->userService->checkProfile();
        if($candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_candidate_view_job_offer', ['id' => $id]);
        }
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_candidate_job_offer');
        }

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $annonce->getEntreprise()->getEntreprise(),
        ]);

        return $this->render('v2/dashboard/job_offer/details.html.twig', [
            'annonce' => $annonce,
            'purchasedContact' => $purchasedContact,
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
