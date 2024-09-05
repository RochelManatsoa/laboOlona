<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Notification;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
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
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        private CreditManager $creditManager,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
    ){}

    #[Route('/', name: 'app_v2_dashboard')]
    public function index(): Response
    {
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

        return $this->render('v2/dashboard/index.html.twig', []);
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
    public function viewProfile(int $id): Response
    {
        /** @var User @user */
        $user = $this->em->getRepository(User::class)->find($id);
        $profile = $this->userService->checkUserProfile($user);
        $recruiter = $this->userService->checkProfile();
        if(!$recruiter instanceof EntrepriseProfile){
            $recruiter = null;
        }

        return $this->render('v2/dashboard/profile/view.html.twig', [
            'profile' => $profile,
            'user' => $user,
            'recruiter' => $recruiter,
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
