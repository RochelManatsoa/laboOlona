<?php

namespace App\Controller\TableauDeBord;

use App\Entity\User;
use App\Entity\Prestation;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Form\PrestationType;
use App\Manager\ProfileManager;
use App\Service\ActivityLogger;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Twig\PrestationExtension;
use App\Manager\JobListingManager;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Credit;
use App\Form\ChangePasswordFormType;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\ContactForm;
use App\Form\Candidat\ApplicationsType;
use App\Security\Voter\PrestationVoter;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TableauDeBord\AssistanceType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BusinessModel\PackageRepository;
use App\Form\Profile\Candidat\Edit\EditCandidateProfile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/tableau-de-bord/candidat')]

class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private CandidatManager $candidatManager,
        private UrlGeneratorInterface $urlGenerator,
        private ActivityLogger $activityLogger,
    ){}


    #[Route('/', name: 'app_tableau_de_bord_candidat')]
    public function index(): Response
    {
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $candidat = $params['candidat'];
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        return $this->render('tableau_de_bord/candidat/index.html.twig', $params);
    }


    #[Route('/mon-profil', name: 'app_tableau_de_bord_candidat_profil')]
    public function profile(): Response
    {
        $params = $this->getData();
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        return $this->render('tableau_de_bord/candidat/mon_profil.html.twig', $params);
    }

    #[Route('/modification-profil', name: 'app_tableau_de_bord_candidat_modification_profil')]
    public function modifprofil(): Response
    {
        return $this->render('tableau_de_bord/candidat/modification_profil.html.twig', $this->getData());
    }

    #[Route('/trouver-des-missions', name: 'app_tableau_de_bord_candidat_trouver_des_missions')]
    public function searchmission(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $limit = 10;
        $params = $this->getData();
        $candidat = $params['candidat'];
        $secteurs = $candidat->getSecteurs();
        $qb = $this->em->getRepository(JobListing::class)->createQueryBuilder('j');
        $qb->where('j.status = :status')
            ->setParameter('status', JobListing::STATUS_PUBLISHED)
            ->andWhere('j.secteur IN (:secteurs)')
            ->setParameter('secteurs', $secteurs)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $offres = $qb->getQuery()->getResult();
        $params['offres'] = $offres;

        return $this->render('tableau_de_bord/candidat/trouver_des_missions.html.twig', $params);
    }

    #[Route('/mes-prestations', name: 'app_tableau_de_bord_candidat_mes_prestations')]
    public function prestation(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $candidat = $params['candidat'];
        $params['prestations']  = $this->em->getRepository(Prestation::class)->paginateCandidatePrestations($candidat, $page);

        return $this->render('tableau_de_bord/candidat/mes_prestations.html.twig', $params);
    }

    #[Route('/mes-candidatures', name: 'app_tableau_de_bord_candidat_mes_candidatures')]
    public function candidature(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $candidat = $params['candidat'];
        $params['candidatures'] = $this->em->getRepository(Applications::class)->findByCandidateProfile($candidat, $page);

        return $this->render('tableau_de_bord/candidat/mes_candidatures.html.twig', $params);
    }

    #[Route('/missions-obtenues', name: 'app_tableau_de_bord_candidat_missions_obtenues')]
    public function missions(): Response
    {
        return $this->render('tableau_de_bord/candidat/missions_obtenues.html.twig', $this->getData());
    }

    #[Route('/view-job-offer/{id}', name: 'app_tableau_de_bord_candidat_view_job_offer')]
    public function viewjoboffer(Request $request, int $id, JobListingManager $jobListingManager, AppExtension $appExtension, ProfileManager $profileManager): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        if ($annonce === null || $annonce->getStatus() === JobListing::STATUS_DELETED || $annonce->getStatus() === JobListing::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le annonce demandé n\'existe pas.');
        }
        $data = $this->getData();
        $jobListingManager->incrementView($annonce, $request->getClientIp());
        $candidat = $data['candidat'];
        $this->activityLogger->logJobLisitinViewActivity($data['currentUser'], $appExtension->generateJobReference($annonce->getId()));
        [$applied, $application] = $jobListingManager->isAppliedByCandidate($annonce, $candidat)[0];
        $form = $this->createForm(ApplicationsType::class, $application);
        $form->handleRequest($request);
        $data['annonce'] = $annonce;
        $data['applied'] = $applied;
        $data['show_recruiter_price'] = $profileManager->getCreditAmount(Credit::ACTION_VIEW_RECRUITER);
        $data['apply_job_price'] = $profileManager->getCreditAmount(Credit::ACTION_APPLY_JOB);
        $data['form'] = $form->createView();

        return $this->render('tableau_de_bord/candidat/view_job_offer.html.twig', $data);
    }

    #[Route('/view-prestation/{prestation}', name: 'app_tableau_de_bord_candidat_view_prestation')]
    #[IsGranted(PrestationVoter::VIEW, subject: 'prestation')]
    public function viewPrestation(Request $request, Prestation $prestation, PrestationManager $prestationManager, AppExtension $appExtension, PrestationExtension $prestationExtension, ProfileManager $profileManager, Security $security): Response
    {
        if($security->isGranted(PrestationVoter::EDIT, null, $prestation)){
            if ($prestation === null || $prestation->getStatus() === Prestation::STATUS_DELETED || $prestation->getStatus() === Prestation::STATUS_PENDING) {
                throw $this->createNotFoundException('Nous sommes désolés, mais le prestation demandé n\'existe pas.');
            }
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

        return $this->render('tableau_de_bord/candidat/view_prestation.html.twig', $data);
    }

    #[Route('/creer-une-prestation', name: 'app_tableau_de_bord_candidat_creation_prestation')]
    public function createpresta(
        Request $request, 
        PrestationManager $prestationManager, 
        ProfileManager $profileManager,
    ): Response
    {
        $params = $this->getData();
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        /** @var Prestation $prestation */
        $prestation = $prestationManager->init($currentUser);
        $creditAmount = $profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
        $boostType = 'PRESTATION_CANDIDATE';
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => $boostType]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($prestation);
            $this->em->flush();

            return $this->redirectToRoute('app_tableau_de_bord_candidat_view_prestation', ['prestation' => $prestation->getId()]);
        }
        $params['form'] = $form->createView();
        $params['creditAmount'] = $creditAmount;

        return $this->render('tableau_de_bord/candidat/creation_prestations.html.twig', $params);
    }

    #[Route('/modifier-une-prestation/{prestation}', name: 'app_tableau_de_bord_candidat_edition_prestation')]
    public function editpresta(
        Request $request, 
        Prestation $prestation, 
        ProfileManager $profileManager,
    ): Response
    {
        $params = $this->getData();
        $creditAmount = $profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
        $boostType = 'PRESTATION_CANDIDATE';
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => $boostType]);
        $form->handleRequest($request);
        $params['form'] = $form->createView();
        $params['creditAmount'] = $creditAmount;

        return $this->render('tableau_de_bord/candidat/creation_prestations.html.twig', $params);
    }

    #[Route('/pack-standard', name: 'app_tableau_de_bord_entreprise_tarifs_standard')]
    public function standard(): Response
    {
        return $this->render('tableau_de_bord/candidat/tarifs_standard.html.twig', $this->getData());
    }

    #[Route('/reseaux-professionnelles', name: 'app_tableau_de_bord_candidat_reseaux_professionnelles')]
    public function socialpro(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $params['allContacts'] = $this->em->getRepository(PurchasedContact::class)->paginateContactsByBuyer($currentUser, $page);

        return $this->render('tableau_de_bord/candidat/reseaux_professionnelles.html.twig', $params);
    }

    #[Route('/se-faire-recommander', name: 'app_tableau_de_bord_candidat_se_faire_recommander')]
    public function recommandation(): Response
    {
        return $this->render('tableau_de_bord/candidat/se_faire_recommander.html.twig', $this->getData());
    }

    #[Route('/annuaire-de-services', name: 'app_tableau_de_bord_candidat_annuaire_de_services')]
    public function annuaire(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $prestations = $this->em->getRepository(Prestation::class)->paginatePrestations(Prestation::STATUS_VALID, $page);
        $params['prestations'] = $prestations;

        return $this->render('tableau_de_bord/candidat/annuaire_de_services.html.twig', $params);
    }

    #[Route('/notification', name: 'app_tableau_de_bord_candidat_notification')]
    public function notification(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $params['notifications'] = $this->em->getRepository(Notification::class)->findByDestinataire($currentUser,null, [], null, $page);
        return $this->render('tableau_de_bord/candidat/notification.html.twig', $params);
    }

    #[Route('/mon-compte', name: 'app_tableau_de_bord_candidat_mon_compte')]
    public function mycompte(Request $request): Response
    {
        $params = $this->getData();
        $candidat = $params['candidat'];
        $form = $this->createForm(EditCandidateProfile::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($candidat);
            $this->em->flush();
            $this->addFlash('success', 'Informations enregistrées');
        }
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', $params);
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_candidat_mise_a_jour_mot_de_passe')]
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

        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', $params);
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_candidat_assistance')]
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
        
        return $this->render('tableau_de_bord/candidat/assistance.html.twig', $params);
    }

    #[Route('/credit', name: 'app_tableau_de_bord_candidat_credit')]
    public function credit(PackageRepository $packageRepository): Response
    {
        $params = $this->getData();
        $params['packages'] = $packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']);
        
        return $this->render('tableau_de_bord/candidat/credit.html.twig', $params);
    }

    #[Route('/boost', name: 'app_tableau_de_bord_candidat_boost')]
    public function boost(): Response
    {
        return $this->render('tableau_de_bord/candidat/boost.html.twig', $this->getData());
    }

    #[Route('/tarifs', name: 'app_tableau_de_bord_candidat_tarifs')]
    public function tarifs(): Response
    {
        return $this->render('tableau_de_bord/candidat/tarifs.html.twig', $this->getData());
    }

    private function getData()
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement.');
        $candidat = $this->userService->checkProfile();
        $data = [];
        $data['currentUser'] = $currentUser;
        $data['candidat'] = $candidat;
        $data['action'] = $this->urlGenerator->generate('app_olona_talents_joblistings', []);
        $data['credit'] = $currentUser->getCredit()->getTotal();
        $data['notificationsCount'] = $this->em->getRepository(Notification::class)->countIsRead($currentUser,false);

        return $data;
    }
}
