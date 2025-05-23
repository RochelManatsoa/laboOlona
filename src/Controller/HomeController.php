<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\MailManager;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Entity\Facebook\Contest;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Security\AppAuthenticator;
use App\Entity\Facebook\ContestEntry;
use App\Entity\Moderateur\Invitation;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\ContactForm;
use App\Service\Annonce\AnnonceService;
use App\Form\Moderateur\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Facebook\ContestEntryFormType;
use App\Manager\Marketing\LeadManager;
use App\Repository\Marketing\SourceRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class HomeController extends AbstractController
{
    public function __construct(private AnnonceService $annonceService)
    {}
    
    #[Route('/v1', name: 'app_olona_talents')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/contact', name: 'app_contact_us')]
    public function contact(
        Request $request, 
        EntityManagerInterface $entityManager, 
        MailerService $mailerService,
        LeadManager $leadManager,
        SourceRepository $sourceRepository
    ): Response
    {
        $sourceEntreprise = $sourceRepository->findOneBy(['slug' => 'formulaire-de-contact-site-olona-talents']);
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $lead = $leadManager->init();
            $lead->setSource($sourceEntreprise);
            $lead->setComment('Formulaire de contact - '.$contactForm->getMessage());
            $lead->setFullName($contactForm->getTitre());
            $lead->setEmail($contactForm->getEmail());
            $lead->setPhone($contactForm->getNumero());
            $leadManager->save($lead);
            $entityManager->persist($contactForm);
            $entityManager->flush();
            $mailerService->sendMultiple(
                ["contact@olona-talents.com", "support@olona-talents.com", "olonaprod@gmail.com"],
                "Nouvelle entrée sur le formulaire de contact",
                "contact.html.twig",
                [
                    'user' => $contactForm,
                ]
            );
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');
        }

        return $this->render('home/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/service', name: 'app_home_service')]
    public function service(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/legal-mentions', name: 'app_home_legal', options: ['sitemap' => true])]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig', []);
    }

    #[Route('/privacy-policy', name: 'app_home_privacy', options: ['sitemap' => true])]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig', []);
    }

    #[Route('/terms-and-conditions', name: 'app_home_terms', options: ['sitemap' => true])]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig', []);
    }

    #[Route('/formulaire', name: 'app_home_form', methods: ['POST'])]
    public function form(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $annonceId = $request->request->get('annonce_id');
            $jobId = $request->request->get('job_id');
            $this->annonceService->add($annonceId);
            if (!$this->getUser()) {
                return $this->redirectToRoute('app_login');
            }
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $jobId]);
        }
    }

    #[Route('/simulateur-portage-salarial', name: 'app_home_simulateur_portage')]
    public function simulateur(): Response 
    {        
        return $this->redirectToRoute('app_home_portage', []);
    }

    #[Route('/portage-salarial', name: 'app_home_portage', options: ['sitemap' => true])]
    public function portage(Request $request, MailManager $mailManager, EntityManagerInterface $entityManager): Response 
    {    
        $contact = new ContactForm;
        $contact->setCreatedAt(new \DateTime());
        $contactForm = $this->createForm(ContactFormType::class, $contact);
        $contactForm->handleRequest($request);
        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $contact = $contactForm->getData();
            $entityManager->persist($contact);
            $entityManager->flush();
            $mailManager->contactForm($contact);
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'contactForm']);
            }
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }
            
        return $this->render('home/simulateur.html.twig', [
            'contactForm' => $contactForm->createView(),
        ]);
    }

    #[Route('/simulateur-entreprise', name: 'app_home_simulateur_entreprise')]
    public function simulateurEntreprise(): Response 
    {
        return $this->redirectToRoute('app_v2_recruiter_simulator');
    }

    #[Route('/authenticate/user', name: 'app_home_authenticate_user', methods: ['POST'])]
    public function authenticateUser(
        Request $request, 
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
        UserService $userService,
    ): Response
    {
        $userEmail = $request->request->get('userEmail');
        $userPassword = $request->request->get('userPassword');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            $response = [
                'status' => 'error',
                'id' => 'user',
                'message' => 'Aucun utilisateur trouvé avec cet e-mail.',
            ];
    
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('turbo_stream/error_message.html.twig', $response);
            }
    
            return $this->json($response, Response::HTTP_NOT_FOUND);
        }

        if(!$userService->checkUserPassword($user, $userPassword)){
            $response = [
                'status' => 'error',
                'id' => 'user',
                'message' => 'Mot de passe incorrect.',
            ];
    
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('turbo_stream/error_message.html.twig', $response);
            }
    
            return $this->json($response, Response::HTTP_NOT_FOUND);
        }
    
        $response = $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
    
        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $responseData = [
                'status' => 'success',
                'id' => 'user',
                'message' => 'Connexion réussie, bienvenue ' . $user->getPrenom(),
            ];
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('turbo_stream/success_message.html.twig', $responseData);
        }
    
        return $response;
    }
}
