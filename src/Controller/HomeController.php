<?php

namespace App\Controller;

use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Moderateur\ContactForm;
use App\Service\Annonce\AnnonceService;
use App\Form\Moderateur\ContactFormType;
use App\Manager\MailManager;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function contact(Request $request, EntityManagerInterface $entityManager, MailerService $mailerService): Response
    {
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $entityManager->persist($contactForm);
            $entityManager->flush();
            $mailerService->sendMultiple(
                ["contact@olona-talents.com", "support@olonatalents.com", "support@olonatalents.com"],
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
}
