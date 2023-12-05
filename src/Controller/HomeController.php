<?php

namespace App\Controller;

use App\Entity\AffiliateTool;
use App\Entity\Entreprise\JobListing;
use App\Entity\Moderateur\ContactForm;
use App\Form\JobListingType;
use App\Form\Moderateur\ContactFormType;
use App\Repository\AffiliateToolRepository;
use App\Service\User\UserService;
use App\Repository\SecteurRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use App\Service\Annonce\AnnonceService;
use App\Service\Mailer\MailerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private SecteurRepository $secteurRepository,
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private AnnonceService $annonceService,
    ){
    }
    
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'sectors' => $this->secteurRepository->findAll(),
            'candidats' => $this->candidateProfileRepository->findTopExperts(),
            'topRanked' => $this->candidateProfileRepository->findTopRanked(),
            'annonces' => $this->jobListingRepository->findBy([
                'status' => JobListing::STATUS_PUBLISHED,
            ]),
        ]);
    }

    #[Route('/contact', name: 'app_home_contact')]
    public function contact(Request $request): Response
    {
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $this->em->persist($contactForm);
            $this->em->flush();
            $this->mailerService->send(
                "contact@olona-talents.com",
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

    #[Route('/legal-mentions', name: 'app_home_legal')]
    public function legal(): Response
    {        
        return $this->render('home/legal.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/privacy-policy', name: 'app_home_privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/terms-and-conditions', name: 'app_home_terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/formulaire', name: 'app_home_form')]
    public function form(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $annonceId = $request->request->get('annonce_id');
            $jobId = $request->request->get('job_id');
            $this->annonceService->add($annonceId);
            if (!$this->getUser()) { 
                return $this->redirectToRoute('app_login'); 
            }
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $jobId ]);
        }
    }
}
