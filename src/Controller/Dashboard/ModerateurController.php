<?php

namespace App\Controller\Dashboard;

use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Repository\Entreprise\JobListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private ProfileManager $profileManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ){
    }
    
    #[Route('/', name: 'app_dashboard_moderateur')]
    public function index(): Response
    {
        return $this->render('dashboard/moderateur/index.html.twig', [
            'controller_name' => 'ModerateurController',
        ]);
    }

    #[Route('/secteurs', name: 'app_dashboard_moderateur_sector')]
    public function sectors(Request $request, SecteurRepository $secteurRepository): Response
    {
        return $this->render('dashboard/moderateur/sectors.html.twig', [
            'sectors' => $secteurRepository->findAll(),
        ]);
    }

    #[Route('/secteur/new', name: 'app_dashboard_moderateur_new_sector')]
    public function sector(Request $request): Response
    {
        $secteur = $this->moderateurManager->initSector();
        $form = $this->createForm(SecteurType::class, $secteur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $secteur = $this->moderateurManager->saveSectorForm($form);

            return $this->redirectToRoute('app_dashboard_moderateur_sector', []);
        }

        return $this->render('dashboard/moderateur/sector.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_moderateur_annonces')]
    public function annonces(Request $request, JobListingRepository $jobListingRepository): Response
    {
        return $this->render('dashboard/moderateur/annonces.html.twig', [
            'annonces' => $jobListingRepository->findAll(),
        ]);
    }

    #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
    public function entreprises(Request $request, EntrepriseProfileRepository $entrepriseProfileRepository): Response
    {
        return $this->render('dashboard/moderateur/entreprises.html.twig', [
            'entreprises' => $entrepriseProfileRepository->findAll(),
        ]);
    }

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    public function candidats(Request $request, CandidateProfileRepository $candidateProfileRepository): Response
    {
        return $this->render('dashboard/moderateur/candidats.html.twig', [
            'candicats' => $candidateProfileRepository->findAll(),
        ]);
    }

    #[Route('/mettings', name: 'app_dashboard_moderateur_mettings')]
    public function mettings(Request $request, MettingRepository $mettingRepository): Response
    {
        return $this->render('dashboard/moderateur/mettings.html.twig', [
            'mettings' => $mettingRepository->findAll(),
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_moderateur_notifications')]
    public function notifications(Request $request, NotificationRepository $notificationRepository): Response
    {
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'notifications' => $notificationRepository->findAll(),
        ]);
    }
}
