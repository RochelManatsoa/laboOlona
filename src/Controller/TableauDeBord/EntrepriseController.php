<?php

namespace App\Controller\TableauDeBord;

use App\Entity\User;
use App\Entity\Prestation;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Manager\ProfileManager;
use App\Service\ActivityLogger;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Twig\PrestationExtension;
use App\Entity\Entreprise\Favoris;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Credit;
use App\Form\ChangePasswordFormType;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\ContactForm;
use App\Form\Entreprise\JobListingType;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TableauDeBord\AssistanceType;
use App\Manager\BusinessModel\CreditManager;
use App\Manager\OlonaTalentsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BusinessModel\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/tableau-de-bord/entreprise')]

class EntrepriseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private UserService $userService,
        private CreditManager $creditManager,
        private ActivityLogger $activityLogger,
    ){}

    #[Route('/', name: 'app_tableau_de_bord_entreprise')]
    public function index(): Response
    {
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $currentUser = $params['currentUser'];
        $profileViews = $this->em->getRepository(ActivityLog::class)->findProfileViewsByUser($currentUser);
        $profiles = [];
        foreach ($profileViews as $profileView) {
            if($profileView->ot_number !== null){
                $profileData = [
                    'date' => $profileView->getTimestamp(), 
                    'referrer' => $profileView->getReferrer(), 
                    'candidat' => $this->em->getRepository(CandidateProfile::class)->find($profileView->ot_number), 
                ];
        
                $profiles[] = $profileData;
            }
        }
        // dd($profiles);
        $params['profileViews'] = $profiles;

        return $this->render('tableau_de_bord/entreprise/index.html.twig', $params);
    }

    #[Route('/cvtheque', name: 'app_tableau_de_bord_entreprise_cvtheque')]
    public function cvtheque(Request $request, OlonaTalentsManager $olonaTalentsManager): Response
    {
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $size = 10; 
        $page = $request->query->get('page', 1);
        $from = ($page - 1) * $size;
        $title = $request->query->get('filter-title', $entreprise->getSecteurs()[0]->getSlug());
        $gender = $request->query->get('filter-gender', null);
        $year = $request->query->get('filter-year', null);
        $searchResults = $olonaTalentsManager->searchEntities('candidates', $from, 10, $title);
        $totalPages = ceil($searchResults['totalResults'] / $size);
        $params['searchResults'] = $searchResults['entities'];
        $params['totalResults'] = $searchResults['totalResults'];
        $params['totalPages'] = $totalPages;
        $params['currentPage'] = $page;


        return $this->render('tableau_de_bord/entreprise/cvtheque.html.twig', $params);
    }
    
    #[Route('/offre-d-emploi', name: 'app_tableau_de_bord_entreprise_offre_emploi')]
    public function offre(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $offres = $this->em->getRepository(JobListing::class)->paginateJobListingsEntrepriseProfiles($page, $entreprise);
        $params['offres'] = $offres;

        return $this->render('tableau_de_bord/entreprise/offre_emploi.html.twig', $params);
    }

    #[Route('/publier-une-annonce', name: 'app_tableau_de_bord_entreprise_publier_une_annonce')]
    public function annonce(
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        $params = $this->getData();
        $form = $this->createForm(JobListingType::class, new JobListing());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing = $form->getData();
            $jobListing->setEntreprise($params['entreprise']);
            $em->persist($jobListing);
            $em->flush();
            $this->addFlash('success', 'Annonce créée avec succès');

            return $this->redirectToRoute('app_tableau_de_bord_entreprise_listes_candidatures');
        }
        $params['form'] = $form->createView();
        
        return $this->render('tableau_de_bord/entreprise/publier_une_annonce.html.twig', $params);
    }

    #[Route('/listes-candidatures', name: 'app_tableau_de_bord_entreprise_listes_candidatures')]
    public function candidatureslist(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $candidatures = $this->em->getRepository(Applications::class)->findByEntrepriseProfile($page, $entreprise->getId());
        $params['candidatures'] = $candidatures;

        return $this->render('tableau_de_bord/entreprise/listes_candidatures.html.twig', $params);
    }

    #[Route('/favoris', name: 'app_tableau_de_bord_entreprise_favoris')]
    public function favoris(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $entreprise = $params['entreprise'];  
        $favoris = $this->em->getRepository(Favoris::class)->paginateFavoris($entreprise, $page);
        $params['favoris'] = $favoris;

        return $this->render('tableau_de_bord/entreprise/favoris.html.twig', $params);
    }

    #[Route('/tarifs', name: 'app_tableau_de_bord_entreprise_tarifs')]
    public function tarifs(): Response
    {
        return $this->render('tableau_de_bord/entreprise/tarifs.html.twig', $this->getData());
    }
    
    #[Route('/choix', name: 'app_tableau_de_bord_entreprise_tarif_choice')]
    public function tchoice(): Response
    {
        return $this->render('tableau_de_bord/entreprise/tarif_choice.html.twig', $this->getData());
    }

    #[Route('/profil-candidat/{id}', name: 'app_tableau_de_bord_entreprise_profil_candidat')]
    public function profilcandidat(Request $request, int $id, CandidatManager $candidatManager, AppExtension $appExtension): Response
    {
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if ($candidat === null || $candidat->getStatus() === CandidateProfile::STATUS_BANNISHED || $candidat->getStatus() === CandidateProfile::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le candidat demandé n\'existe pas.');
        }
        $candidatManager->incrementView($candidat, $request->getClientIp());
        $data = $this->getData();
        $this->activityLogger->logProfileViewActivity($data['currentUser'], $appExtension->generatePseudo($candidat));
        $data['candidat'] = $candidat;
        $data['experiences'] = $candidatManager->getExperiencesSortedByDate($candidat);
        $data['competences'] = $candidatManager->getCompetencesSortedByNote($candidat);
        $data['langages'] = $candidatManager->getLangagesSortedByNiveau($candidat);

        return $this->render('tableau_de_bord/entreprise/profil_candidat.html.twig', $data);
    }

    #[Route('/view-prestation/{id}', name: 'app_tableau_de_bord_entreprise_view_prestation')]
    public function viewPrestation(Request $request, int $id, PrestationManager $prestationManager, AppExtension $appExtension, PrestationExtension $prestationExtension, ProfileManager $profileManager): Response
    {
        $prestation = $this->em->getRepository(Prestation::class)->find($id);
        if ($prestation === null || $prestation->getStatus() === Prestation::STATUS_DELETED || $prestation->getStatus() === Prestation::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le prestation demandé n\'existe pas.');
        }
        $data = $this->getData();
        $prestationManager->incrementView($prestation, $request->getClientIp());
        $currentUser = $data['currentUser'];
        $owner = false;
        $creater = $prestationExtension->getUserPrestation($prestation);
        if($creater == $currentUser){
            $owner = true;
        }
        $this->activityLogger->logPrestationViewActivity($data['currentUser'], $appExtension->generateprestationReference($prestation->getId()));
        $data['prestation'] = $prestation;
        $data['owner'] = $owner;
        $data['creater'] = $creater;
        $data['showContactPrice'] = $profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);

        return $this->render('tableau_de_bord/entreprise/view_prestation.html.twig', $data);
    }

    #[Route('/annuaire-de-services', name: 'app_tableau_de_bord_entreprise_annuaire_de_services')]
    public function annuaire(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $prestations = $this->em->getRepository(Prestation::class)->paginatePrestations(Prestation::STATUS_VALID, $page);
        $params['prestations'] = $prestations;

        return $this->render('tableau_de_bord/entreprise/annuaire_de_services.html.twig', $params);
    }
    
    #[Route('/profil-public', name: 'app_tableau_de_bord_entreprise_profil_public')]
    public function profilpublic(): Response
    {
        return $this->render('tableau_de_bord/entreprise/profil_public.html.twig', $this->getData());
    }

    #[Route('/notification', name: 'app_tableau_de_bord_entreprise_notification')]
    public function notification(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $params['notifications'] = $this->em->getRepository(Notification::class)->findByDestinataire($currentUser,null, [], null, $page);

        return $this->render('tableau_de_bord/entreprise/notification.html.twig', $params);
    }

    #[Route('/mon-compte', name: 'app_tableau_de_bord_entreprise_mon_compte')]
    public function mycompte(Request $request): Response
    {
        $params = $this->getData();
        $entreprise = $params['entreprise'];
        $form = $this->createForm(EditEntrepriseType::class, $entreprise);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($entreprise);
            $this->em->flush();
            $this->addFlash('success', 'Informations enregistrées');
        }
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/entreprise/mon_compte.html.twig', $params);
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_entreprise_mise_a_jour_mot_de_passe')]
    public function updatepassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword(
                $currentUser,
                $form->get('plainPassword')->getData()
            );

            $currentUser->setPassword($encodedPassword);
            $this->em->flush();

            return $this->redirectToRoute('app_home');
        }

        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/entreprise/mise_a_jour_mot_de_passe.html.twig', $params);
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_entreprise_assistance')]
    public function assistance(Request $request, EntityManagerInterface $entityManager, MailerService $mailerService): Response
    {
        $params = $this->getData();
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(AssistanceType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
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
        $params['form'] = $form->createView();
        
        return $this->render('tableau_de_bord/entreprise/assistance.html.twig', $params);
    }

    #[Route('/credit', name: 'app_tableau_de_bord_entreprise_credit')]
    public function credit(PackageRepository $packageRepository): Response
    {
        $params = $this->getData();
        $params['packages'] = $packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']);
        
        return $this->render('tableau_de_bord/entreprise/credit.html.twig', $params);
    }

    #[Route('/boost', name: 'app_tableau_de_bord_entreprise_boost')]
    public function boost(): Response
    {
        return $this->render('tableau_de_bord/entreprise/boost.html.twig', $this->getData());
    }

    private function getData()
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $entreprise = $this->userService->checkProfile();
        $data = [];
        $data['currentUser'] = $currentUser;
        $data['entreprise'] = $entreprise;
        $data['credit'] = $currentUser->getCredit()->getTotal();
        $data['notificationsCount'] = $this->em->getRepository(Notification::class)->countIsRead($currentUser,false);
        $data['favorisCount'] = count($entreprise->getFavoris());

        return $data;
    }
}
