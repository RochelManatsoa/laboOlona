<?php

namespace App\Controller\TableauDeBord;

use App\Entity\User;
use App\Entity\Prestation;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Data\QuerySearchData;
use App\Entity\Finance\Devise;
use App\Twig\FinanceExtension;
use App\Manager\ProfileManager;
use App\Service\ActivityLogger;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Twig\PrestationExtension;
use App\Entity\Entreprise\Favoris;
use App\Manager\JobListingManager;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Credit;
use App\Form\ChangePasswordFormType;
use App\Form\Entreprise\AnnonceType;
use App\Manager\OlonaTalentsManager;
use App\Entity\BusinessModel\Package;
use App\Entity\Entreprise\JobListing;
use App\Form\BusinessModel\OrderType;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\ContactForm;
use App\Form\Entreprise\JobListingType;
use App\Security\Voter\JobListingVoter;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Form\TableauDeBord\AssistanceType;
use App\Form\BusinessModel\TransactionType;
use App\Manager\BusinessModel\OrderManager;
use Symfony\Bundle\SecurityBundle\Security;
use App\Manager\BusinessModel\CreditManager;
use App\Repository\Finance\DeviseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use App\Repository\BusinessModel\PackageRepository;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BusinessModel\PurchasedContactRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        private MailerService $mailerService,
    ){}

    #[Route('/', name: 'app_tableau_de_bord_entreprise')]
    public function index(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
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
    
    #[Route('/abonnement', name: 'app_tableau_de_bord_entreprise_abonnement')]
    public function subcription(
        OrderManager $orderManager, 
        Request $request, 
        FinanceExtension $financeExtension, 
        TransactionManager $transactionManager,
        DeviseRepository $deviseRepository
    ): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $package = $this->em->getRepository(Package::class)->findOneBy(['slug' => 'abonnement']);
        /** @var Devise $currency */
        $currency = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $order = $orderManager->init();
        $order->setPackage($package);
        $order->setCurrency($currency);
        $order->setTotalAmount($package->getPrice());
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->getData();
            $transaction = $order->getTransaction();
            if(!$transaction instanceof Transaction){
                $transaction = $transactionManager->init();
                $transaction->setCommand($order);
            }
            $transaction->setPackage($package);
            $transaction->setAmount($package->getPrice());
            $transactionManager->save($transaction);
            $orderManager->save($order);
            
            return $this->redirectToRoute('app_tableau_de_bord_entreprise_mobile_money_checkout', [
                'orderNumber' => $order->getOrderNumber()
            ]);
        } 
        $params['devise'] = $deviseRepository->findOneBy(['slug' => 'euro']);
        $params['package'] = $package;
        $params['form'] = $form->createView();
        $params['price'] = $financeExtension->convertToEuro($package->getPrice(), $currency);

        return $this->render('tableau_de_bord/entreprise/abonnement.html.twig', $params);
    }

    #[Route('/annuaire-de-services', name: 'app_tableau_de_bord_entreprise_annuaire_de_services')]
    public function annuaire(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $size = $request->query->get('size', 10);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $prestations = $this->em->getRepository(Prestation::class)->paginatePrestations(Prestation::STATUS_VALID, $page, $size);
        $params['prestations'] = $prestations;
        $params['size'] = $size;

        return $this->render('tableau_de_bord/entreprise/annuaire_de_services.html.twig', $params);
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_entreprise_assistance')]
    public function assistance(Request $request, EntityManagerInterface $entityManager, MailerService $mailerService): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
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

    #[Route('/boost', name: 'app_tableau_de_bord_entreprise_boost')]
    public function boost(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/entreprise/boost.html.twig', $params);
    }

    #[Route('/credit/{slug}', name: 'app_tableau_de_bord_entreprise_credit')]
    public function credit(Package $package, 
        OrderManager $orderManager, 
        Request $request, 
        TransactionManager $transactionManager, 
        FinanceExtension $financeExtension
    ): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['package'] = $package;
        /** @var Devise $devise */
        $devise = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $order = $orderManager->init();
        $order->setPackage($package);
        $order->setCurrency($devise);
        $order->setTotalAmount($package->getPrice());
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->getData();
            $transaction = $order->getTransaction();
            if(!$transaction instanceof Transaction){
                $transaction = $transactionManager->init();
                $transaction->setCommand($order);
            }
            $transaction->setPackage($package);
            $transaction->setAmount($package->getPrice());
            $transactionManager->save($transaction);
            $orderManager->save($order);

            return $this->redirectToRoute('app_tableau_de_bord_entreprise_mobile_money_checkout', [
                'orderNumber' => $order->getOrderNumber()
            ]);
        } 
        $params['devise'] = $devise;
        $params['form'] = $form->createView();
        $params['price'] = $financeExtension->convertToEuro($package->getPrice(), $devise);
        
        return $this->render('tableau_de_bord/entreprise/credit.html.twig', $params);
    }

    #[Route('/cvtheque', name: 'app_tableau_de_bord_entreprise_cvtheque')]
    public function cvtheque(Request $request, OlonaTalentsManager $olonaTalentsManager): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $entreprise = $params['entreprise'];
        $filterTitle = $entreprise->getSecteurs()[0]->getSlug();
        $size = $request->query->get('size', 10);
        $page = $request->query->get('page', 1);
        $from = ($page - 1) * $size;
        $title = $request->query->get('filter-title', $filterTitle);
        $gender = $request->query->get('filter-gender', null);
        $year = $request->query->get('filter-year', null);
        $searchResults = $olonaTalentsManager->searchEntities('candidates', $from, $size, $title);
        $totalPages = ceil($searchResults['totalResults'] / $size);
        $params['searchResults'] = $searchResults['entities'];
        $params['totalResults'] = $searchResults['totalResults'];
        $params['totalPages'] = $totalPages;
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['filterTitle'] = $filterTitle;


        return $this->render('tableau_de_bord/entreprise/cvtheque.html.twig', $params);
    }

    #[Route('/favoris', name: 'app_tableau_de_bord_entreprise_favoris')]
    public function favoris(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $entreprise = $params['entreprise'];  
        $favoris = $this->em->getRepository(Favoris::class)->paginateFavoris($entreprise, $page);
        $params['favoris'] = $favoris;

        return $this->render('tableau_de_bord/entreprise/favoris.html.twig', $params);
    }

    #[Route('/listes-candidatures', name: 'app_tableau_de_bord_entreprise_listes_candidatures')]
    public function candidatureslist(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $entreprise = $params['entreprise'];
        $candidatures = $this->em->getRepository(Applications::class)->findByEntrepriseProfile($page, $entreprise->getId());
        $params['candidatures'] = $candidatures;

        return $this->render('tableau_de_bord/entreprise/listes_candidatures.html.twig', $params);
    }
    
    #[Route('/mes-commandes', name: 'app_tableau_de_bord_entreprise_mes_commandes')]
    public function orders(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['orders'] = $this->em->getRepository(Order::class)->filterByUser(new QuerySearchData);

        return $this->render('tableau_de_bord/entreprise/mes_commandes.html.twig', $params);
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_entreprise_mise_a_jour_mot_de_passe')]
    public function updatepassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
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

    #[Route('/mon-compte', name: 'app_tableau_de_bord_entreprise_mon_compte')]
    public function mycompte(Request $request): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
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

    #[Route('/notification', name: 'app_tableau_de_bord_entreprise_notification')]
    public function notification(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $params['notifications'] = $this->em->getRepository(Notification::class)->findByDestinataire($currentUser,null, [], null, $page);

        return $this->render('tableau_de_bord/entreprise/notification.html.twig', $params);
    }
    
    #[Route('/offre-d-emploi', name: 'app_tableau_de_bord_entreprise_offre_emploi')]
    public function offre(Request $request, JobListingRepository $jobListingRepository, JobListingManager $jobListingManager): Response
    {
        $page = $request->query->get('page', 1);
        $status = $request->query->get('status', JobListing::STATUS_PUBLISHED);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $entreprise = $params['entreprise'];
        $offres = $jobListingRepository->paginateJobListingsEntrepriseProfiles($entreprise, $page, $status);
        $params['offres'] = $offres;
        $params['count'] = $jobListingRepository->countAllByEntreprise($entreprise);
        $params['countStatus'] = $jobListingRepository->countStatusByEntreprise($entreprise, $status);
        $params['statuses'] = $jobListingManager->getStatuses();
        $params['labels'] = JobListing::getLabels();
        $params['selectedStatus'] = $status;

        return $this->render('tableau_de_bord/entreprise/offre_emploi.html.twig', $params);
    }

    #[Route('/paiement/{orderNumber}', name: 'app_tableau_de_bord_entreprise_mobile_money_checkout')]
    public function mobileMoney(Order $order, Request $request, TransactionManager $transactionManager): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $mobileMoney = $order->getPaymentMethod();
        $transaction = $order->getTransaction();
        if(!$transaction instanceof Transaction){
            $transaction = $transactionManager->init();
            $transaction->setCommand($order);
            $transactionManager->save($transaction);
        }
        $transaction->setTypeTransaction($mobileMoney);
        $transaction->setCommand($order);
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        $this->activityLogger->logPageViewActivity($currentUser, '/mobile-money/_order');
        
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction = $form->getData();
            $command = $form->getData()->getCommand();
            $command->setStatus(Order::STATUS_PROCESSING);
            $transaction->setPackage($command->getPackage());
            $transaction->setUpdatedAt(new \DateTime());
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transactionManager->save($transaction);

            /** On envoi un mail */
            $this->mailerService->sendMultiple(
                ["contact@olona-talents.com", "admin@olona-talents.com", "aolonaprodadmi@gmail.com", "partenaires@olona-talents.com"],
                "Paiement sur Olona Talents",
                "notification_paiement.html.twig",
                [
                    'user' => $currentUser,
                    'transaction' => $transaction,
                    'order' => $order,
                    'dashboard_url' => $this->generateUrl('app_dashboard_moderateur_business_model_transaction_view', [
                        'transaction' => $transaction->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            
            return $this->redirectToRoute('app_tableau_de_bord_entreprise_mes_commandes');
        }
        $params['status'] = 'Succès';
        $params['order'] = $order;
        $params['payment'] = true;
        $params['mobileMoney'] = $mobileMoney;
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/entreprise/paiement.html.twig', $params);
    }

    #[Route('/profil-candidat/{id}', name: 'app_tableau_de_bord_entreprise_profil_candidat')]
    public function profilcandidat(Request $request, int $id, CandidatManager $candidatManager, AppExtension $appExtension, PurchasedContactRepository $contactRepository, ProfileManager $profileManager): Response
    {
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if ($candidat === null || $candidat->getStatus() === CandidateProfile::STATUS_BANNISHED || $candidat->getStatus() === CandidateProfile::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le candidat demandé n\'existe pas.');
        }
        $candidatManager->incrementView($candidat, $request->getClientIp());
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $this->activityLogger->logProfileViewActivity($params['currentUser'], $appExtension->generatePseudo($candidat));
        $params['candidat'] = $candidat;
        $currentUser = $params['currentUser'];
        $params['show_candidate_price'] = $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);
        $params['experiences'] = $candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $candidatManager->getLangagesSortedByNiveau($candidat);
        $params['purchasedContact'] = $contactRepository->findOneBy(['buyer' => $currentUser,'contact' => $candidat->getCandidat()]);

        return $this->render('tableau_de_bord/entreprise/profil_candidat.html.twig', $params);
    }
    
    #[Route('/profil-public', name: 'app_tableau_de_bord_entreprise_profil_public')]
    public function profilpublic(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/entreprise/profil_public.html.twig', $params);
    }

    #[Route('/plublier-une-annonce/', name: 'app_tableau_de_bord_entreprise_creer_une_annonce')]
    public function annonceCreate(
        Request $request,
        JobListingManager $jobListingManager,
        EntityManagerInterface $em,
    ): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $entreprise = $params['entreprise'];
        $currentUser = $params['currentUser'];
        $form = $this->createForm(AnnonceType::class, $jobListingManager->init($entreprise));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $jobListingManager->handleJobListingSubmission($form->getData(), $currentUser);
            if($response['success'] === true){
                $jobListing = $form->getData();
                $jobListing->setEntreprise($params['entreprise']);
                $em->persist($jobListing);
                $em->flush();
                $this->addFlash('success', 'Annonce créée avec succès');
                return $this->redirectToRoute('app_tableau_de_bord_entreprise_view_job_offer', ['id' => $jobListing->getId()]);
            }
            $this->addFlash('dark', 'Votre credit est insufisant');

        }
        $params['form'] = $form->createView();
        
        return $this->render('tableau_de_bord/entreprise/publier_une_annonce.html.twig', $params);
    }

    #[Route('/reseaux-professionnelles', name: 'app_tableau_de_bord_entreprise_reseaux_professionnelles')]
    public function socialpro(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $params['allContacts'] = $this->em->getRepository(PurchasedContact::class)->paginateContactsByBuyer($currentUser, $page);

        return $this->render('tableau_de_bord/entreprise/reseaux_professionnelles.html.twig', $params);
    }
    
    #[Route('/tarif-choix', name: 'app_tableau_de_bord_entreprise_tarif_choice')]
    public function tchoice(PackageRepository $packageRepository, DeviseRepository $deviseRepository): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['packages'] = $packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']);
        $params['devise'] = $deviseRepository->findOneBy(['slug' => 'euro']);

        return $this->render('tableau_de_bord/entreprise/tarif_choice.html.twig', $params);
    }

    #[Route('/tarifs-standard', name: 'app_tableau_de_bord_entreprise_tarifs_standard')]
    public function standard(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/entreprise/tarifs_standard.html.twig', $params);
    }

    #[Route('/tarifs', name: 'app_tableau_de_bord_entreprise_tarifs')]
    public function tarifs(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/entreprise/tarifs.html.twig', $params);
    }

    #[Route('/trouver-des-missions', name: 'app_tableau_de_bord_entreprise_trouver_des_missions')]
    public function searchmission(Request $request): Response
    {        
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['joblistings'] = $this->em->getRepository(JobListing::class)->paginateJobListings(JobListing::STATUS_PUBLISHED, $page, $size);
        $params['joblistings_boost'] = $this->em->getRepository(JobListing::class)->paginateJobListings(JobListing::STATUS_FEATURED, $page, 6);

        return $this->render('tableau_de_bord/entreprise/trouver_des_missions.html.twig', $params);
    }

    #[Route('/detail-annonce/{id}', name: 'app_tableau_de_bord_entreprise_view_job_offer')]
    public function viewjoboffer(Request $request, int $id, JobListingManager $jobListingManager, AppExtension $appExtension, Security $security): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $owner = false;
        
        if($annonce === null) {
            throw $this->createNotFoundException('Nous sommes désolés, mais l\'annonce demandée n\'existe pas.');
        }

        // Vérifie si l'utilisateur actuel est propriétaire de l'annonce
        if($annonce->getEntreprise()->getId() == $params['entreprise']->getId()){
            $owner = true;
        }

        // Si ce n'est pas le propriétaire, on vérifie le statut
        if (!$owner && ($annonce->getStatus() === JobListing::STATUS_DELETED || $annonce->getStatus() === JobListing::STATUS_PENDING)) {
            throw $this->createNotFoundException('Nous sommes désolés, mais l\'annonce demandée n\'existe pas.');
        }

        // Le reste du code reste inchangé
        $jobListingManager->incrementView($annonce, $request->getClientIp());
        $this->activityLogger->logJobLisitinViewActivity($params['currentUser'], $appExtension->generateJobReference($annonce->getId()));
        $params['annonce'] = $annonce;
        $params['owner'] = $owner;

        return $this->render('tableau_de_bord/entreprise/view_job_offer.html.twig', $params);
    }

    #[Route('/modifier-une-annonce/{jobListing}', name: 'app_tableau_de_bord_entreprise_modifier_une_annonce')]
    #[IsGranted(JobListingVoter::EDIT, subject: 'jobListing')]
    public function annonceEdit(
        Request $request,
        JobListing $jobListing,
        JobListingManager $jobListingManager,
        EntityManagerInterface $em,
    ): Response
    {
        $params = $this->getData();
        $currentUser = $params['currentUser'];
        $form = $this->createForm(JobListingType::class, $jobListing);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $jobListingManager->handleJobListingSubmission($form->getData(), $currentUser);
            if($response['success'] === true){
                $jobListing = $form->getData();
                $jobListing->setEntreprise($params['entreprise']);
                $em->persist($jobListing);
                $em->flush();
                $this->addFlash('success', 'Annonce créée avec succès');
                return $this->redirectToRoute('app_tableau_de_bord_entreprise_view_job_offer', ['id' => $jobListing->getId()]);
            }
            $this->addFlash('dark', 'Votre credit est insufisant');
        }
        $params['form'] = $form->createView();
        
        return $this->render('tableau_de_bord/entreprise/publier_une_annonce.html.twig', $params);
    }

    #[Route('/detail-prestation/{id}', name: 'app_tableau_de_bord_entreprise_view_prestation')]
    public function viewPrestation(Request $request, int $id, PrestationManager $prestationManager, AppExtension $appExtension, PrestationExtension $prestationExtension, ProfileManager $profileManager): Response
    {
        $prestation = $this->em->getRepository(Prestation::class)->find($id);
        if ($prestation === null || $prestation->getStatus() === Prestation::STATUS_DELETED || $prestation->getStatus() === Prestation::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le prestation demandé n\'existe pas.');
        }
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $prestationManager->incrementView($prestation, $request->getClientIp());
        $currentUser = $params['currentUser'];
        $owner = false;
        $creater = $prestationExtension->getUserPrestation($prestation);
        if($creater == $currentUser){
            $owner = true;
        }
        $this->activityLogger->logPrestationViewActivity($params['currentUser'], $appExtension->generateprestationReference($prestation->getId()));
        $params['prestation'] = $prestation;
        $params['owner'] = $owner;
        $params['creater'] = $creater;
        $params['showContactPrice'] = $profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);

        return $this->render('tableau_de_bord/entreprise/view_prestation.html.twig', $params);
    }

    public function getData()
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
        $data['contactsCount'] = $this->em->getRepository(PurchasedContact::class)->countContacts($currentUser);
        $data['candidaturesCount'] = $this->em->getRepository(Applications::class)->countByEntrepriseProfile($entreprise->getId());

        return $data;
    }
}
