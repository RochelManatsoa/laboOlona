<?php

namespace App\Controller\TableauDeBord;

use App\Entity\User;
use App\Entity\Prestation;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Form\PrestationType;
use App\Data\QuerySearchData;
use App\Service\FileUploader;
use App\Entity\Finance\Devise;
use App\Twig\FinanceExtension;
use App\Manager\ProfileManager;
use App\Service\ActivityLogger;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Twig\PrestationExtension;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\JobListingManager;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Order;
use App\Manager\ApplicationManager;
use App\Entity\BusinessModel\Credit;
use App\Form\ChangePasswordFormType;
use App\Entity\BusinessModel\Package;
use App\Entity\Entreprise\JobListing;
use App\Form\BusinessModel\OrderType;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\ContactForm;
use App\Form\Candidat\ApplicationsType;
use App\Security\Voter\PrestationVoter;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Form\TableauDeBord\AssistanceType;
use App\Form\BusinessModel\TransactionType;
use App\Manager\BusinessModel\OrderManager;
use Symfony\Bundle\SecurityBundle\Security;
use App\Form\Boost\CreateCandidateBoostType;
use App\Manager\BusinessModel\CreditManager;
use App\Repository\Finance\DeviseRepository;
use App\Entity\BusinessModel\BoostVisibility;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use App\Repository\BusinessModel\PackageRepository;
use App\Repository\Entreprise\JobListingRepository;
use App\Manager\BusinessModel\BoostVisibilityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\Profile\Candidat\Edit\EditCandidateProfile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BusinessModel\PurchasedContactRepository;
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
        private MailerService $mailerService,
    ){}


    #[Route('/', name: 'app_tableau_de_bord_candidat')]
    public function index(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $completion = $candidat->getProfileCompletion();
        $radius = 64.82;
        $angle = (180 * $completion / 100); 
        $radian = deg2rad($angle - 180);         
        $endX = 110 + $radius * cos($radian);
        $endY = 110 + $radius * sin($radian);
        $largeArcFlag = $completion > 50 ? 1 : 0;
        $currentUser = $params['currentUser'];
        
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        $params['completion'] = $completion;
        $params['endX'] = $endX;
        $params['endY'] = $endY;
        $params['largeArcFlag'] = $largeArcFlag;

        return $this->render('tableau_de_bord/candidat/index.html.twig', $params);
    }
    
    #[Route('/abonnement', name: 'app_tableau_de_bord_candidat_abonnement')]
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
        $package = $this->em->getRepository(Package::class)->findOneBy(['slug' => 'abonnement-candidat']);
        /** @var Devise $currency */
        $currency = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $order = $orderManager->init();
        $order->setPackage($package);
        $order->setCurrency($currency);
        $order->setTotalAmount($financeExtension->convertToEuro($package->getPrice(), $currency));
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
            
            return $this->redirectToRoute('app_tableau_de_bord_candidat_mobile_money_checkout', [
                'orderNumber' => $order->getOrderNumber()
            ]);
        } 
        $params['devise'] = $deviseRepository->findOneBy(['slug' => 'euro']);
        $params['package'] = $package;
        $params['form'] = $form->createView();
        $params['price'] = $financeExtension->convertToEuro($package->getPrice(), $currency);

        return $this->render('tableau_de_bord/candidat/abonnement.html.twig', $params);
    }

    #[Route('/annuaire-de-services', name: 'app_tableau_de_bord_candidat_annuaire_de_services')]
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

        return $this->render('tableau_de_bord/candidat/annuaire_de_services.html.twig', $params);
    }

    #[Route('/assistance', name: 'app_tableau_de_bord_candidat_assistance')]
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
        
        return $this->render('tableau_de_bord/candidat/assistance.html.twig', $params);
    }

    #[Route('/boost', name: 'app_tableau_de_bord_candidat_boost')]
    public function boost(Request $request, BoostVisibilityManager $boostVisibilityManager, CreditManager $creditManager): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        $form = $this->createForm(CreateCandidateBoostType::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $boostOption = $form->get('boost')->getData(); 
            $candidat = $form->getData();
            $visibilityBoost = $candidat->getBoostVisibility();
            if(!$visibilityBoost instanceof BoostVisibility){
                $visibilityBoost = $boostVisibilityManager->init($boostOption);
            }
            $visibilityBoost = $boostVisibilityManager->update($visibilityBoost, $boostOption);
            $creditManager->adjustCredits($currentUser, $boostOption->getCredit(), "Boost Profil Olona Talents");
            $candidat->setBoostVisibility($visibilityBoost);
            $candidat->setStatus(CandidateProfile::STATUS_FEATURED);
            $this->em->persist($candidat);
            $this->em->flush();
            $this->addFlash('success', 'Boost enregistré');
            return $this->redirectToRoute('app_tableau_de_bord_candidat');
        }
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/candidat/boost.html.twig', $params);
    }

    #[Route('/creer-une-prestation', name: 'app_tableau_de_bord_candidat_creation_prestation')]
    public function createpresta(
        Request $request, 
        PrestationManager $prestationManager, 
        ProfileManager $profileManager,
    ): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
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

    #[Route('/credit/{slug}', name: 'app_tableau_de_bord_candidat_credit')]
    public function credit(
        Package $package, 
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
        $order->setTotalAmount($financeExtension->convertToEuro($package->getPrice(), $devise));
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
            
            return $this->redirectToRoute('app_tableau_de_bord_candidat_mobile_money_checkout', [
                'orderNumber' => $order->getOrderNumber()
            ]);
        } 
        $params['devise'] = $devise;
        $params['form'] = $form->createView();
        $params['price'] = $financeExtension->convertToEuro($package->getPrice(), $devise);
        
        return $this->render('tableau_de_bord/candidat/credit.html.twig', $params);
    }

    #[Route('/mes-candidatures', name: 'app_tableau_de_bord_candidat_mes_candidatures')]
    public function candidature(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $params['candidatures'] = $this->em->getRepository(Applications::class)->findByCandidateProfile($candidat, $page);

        return $this->render('tableau_de_bord/candidat/mes_candidatures.html.twig', $params);
    }
    
    #[Route('/mes-commandes', name: 'app_tableau_de_bord_candidat_mes_commandes')]
    public function orders(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['orders'] = $this->em->getRepository(Order::class)->filterByUser(new QuerySearchData);

        return $this->render('tableau_de_bord/candidat/mes_commandes.html.twig', $params);
    }

    #[Route('/mes-prestations', name: 'app_tableau_de_bord_candidat_mes_prestations')]
    public function prestation(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $params['prestations']  = $this->em->getRepository(Prestation::class)->paginateCandidatePrestations($candidat, $page);

        return $this->render('tableau_de_bord/candidat/mes_prestations.html.twig', $params);
    }

    #[Route('/mise-a-jour-mot-de-passe', name: 'app_tableau_de_bord_candidat_mise_a_jour_mot_de_passe')]
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

        return $this->render('tableau_de_bord/candidat/mise_a_jour_mot_de_passe.html.twig', $params);
    }

    #[Route('/missions-obtenues', name: 'app_tableau_de_bord_candidat_missions_obtenues')]
    public function missions(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/candidat/missions_obtenues.html.twig', $params);
    }

    #[Route('/modification-profil', name: 'app_tableau_de_bord_candidat_modification_profil')]
    public function modifprofil(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/candidat/modification_profil.html.twig', $params);
    }

    #[Route('/mon-profil', name: 'app_tableau_de_bord_candidat_profil')]
    public function profile(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        $params['experiences']  = $this->candidatManager->getExperiencesSortedByDate($candidat);
        $params['competences'] = $this->candidatManager->getCompetencesSortedByNote($candidat);
        $params['langages'] = $this->candidatManager->getLangagesSortedByNiveau($candidat);
        $params['activities'] = $this->em->getRepository(ActivityLog::class)->findUserLogs($currentUser);

        return $this->render('tableau_de_bord/candidat/mon_profil.html.twig', $params);
    }

    #[Route('/mon-compte', name: 'app_tableau_de_bord_candidat_mon_compte')]
    public function mycompte(Request $request, FileUploader $fileUploader, ProfileManager $profileManager): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $candidat = $params['candidat'];
        $form = $this->createForm(EditCandidateProfile::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $fileName = $fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);
                $profileManager->saveCV($fileName, $candidat);
            }
            $this->em->persist($candidat);
            $this->em->flush();
            $this->addFlash('success', 'Informations enregistrées');
        }
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/candidat/mon_compte.html.twig', $params);
    }

    #[Route('/notification', name: 'app_tableau_de_bord_candidat_notification')]
    public function notification(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $params['notifications'] = $this->em->getRepository(Notification::class)->findByDestinataire($currentUser,null, [], null, $page);
        return $this->render('tableau_de_bord/candidat/notification.html.twig', $params);
    }

    #[Route('/paiement/{orderNumber}', name: 'app_tableau_de_bord_candidat_mobile_money_checkout')]
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
            
            return $this->redirectToRoute('app_tableau_de_bord_candidat_mes_commandes');
        }
        $params['status'] = 'Succès';
        $params['order'] = $order;
        $params['payment'] = true;
        $params['mobileMoney'] = $mobileMoney;
        $params['form'] = $form->createView();

        return $this->render('tableau_de_bord/candidat/paiement.html.twig', $params);
    }

    #[Route('/reseaux-professionnelles', name: 'app_tableau_de_bord_candidat_reseaux_professionnelles')]
    public function socialpro(Request $request): Response
    {
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $params['allContacts'] = $this->em->getRepository(PurchasedContact::class)->paginateContactsByBuyer($currentUser, $page);

        return $this->render('tableau_de_bord/candidat/reseaux_professionnelles.html.twig', $params);
    }

    #[Route('/se-faire-recommander', name: 'app_tableau_de_bord_candidat_se_faire_recommander')]
    public function recommandation(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/candidat/se_faire_recommander.html.twig', $params);
    }

    #[Route('/tarif-standard', name: 'app_tableau_de_bord_candidat_tarifs_standard')]
    public function standard(PackageRepository $packageRepository, DeviseRepository $deviseRepository): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $params['packages'] = $packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']);
        $params['devise'] = $deviseRepository->findOneBy(['slug' => 'euro']);

        return $this->render('tableau_de_bord/candidat/tarifs_standard.html.twig', $params);
    }

    #[Route('/tarifs', name: 'app_tableau_de_bord_candidat_tarifs')]
    public function tarifs(): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        return $this->render('tableau_de_bord/candidat/tarifs.html.twig', $params);
    }

    #[Route('/trouver-des-missions', name: 'app_tableau_de_bord_candidat_trouver_des_missions')]
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

        return $this->render('tableau_de_bord/candidat/trouver_des_missions.html.twig', $params);
    }

    #[Route('/detail-entreprise/{id}', name: 'app_tableau_de_bord_candidat_view_recruiter')]
    public function viewRecruiter(
        Request $request, 
        int $id, 
        AppExtension $appExtension, 
        ProfileManager $profileManager,
        PurchasedContactRepository $contactRepository,
        JobListingRepository $jobListingRepository,
    ): Response
    {
        $entreprise = $this->em->getRepository(EntrepriseProfile::class)->find($id);
        if ($entreprise === null || $entreprise->getStatus() === EntrepriseProfile::STATUS_BANNED || $entreprise->getStatus() === EntrepriseProfile::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais l\'entreprise demandée n\'existe pas.');
        }
        $page = $request->query->get('page', 1);
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $currentUser = $params['currentUser'];
        $this->activityLogger->logEntrepriseViewActivity($params['currentUser'], $appExtension->generateReference($entreprise));
        $params['entreprise'] = $entreprise;
        $params['joblistings'] = $jobListingRepository->paginateJobListingsEntrepriseProfiles($entreprise, $page, JobListing::STATUS_PUBLISHED);
        $params['show_recruiter_price'] = $profileManager->getCreditAmount(Credit::ACTION_VIEW_RECRUITER);
        $params['purchasedContact'] = $contactRepository->findOneBy(['buyer' => $currentUser,'contact' => $entreprise->getEntreprise()]);

        return $this->render('tableau_de_bord/candidat/view_entreprise.html.twig', $params);
    }

    #[Route('/detail-annonce/{id}', name: 'app_tableau_de_bord_candidat_view_job_offer')]
    public function viewjoboffer(
        Request $request, 
        int $id, 
        JobListingManager $jobListingManager, 
        AppExtension $appExtension, 
        ProfileManager $profileManager,
        CreditManager $creditManager,
        ApplicationManager $applicationManager,
    ): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        if ($annonce === null || $annonce->getStatus() === JobListing::STATUS_DELETED || $annonce->getStatus() === JobListing::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le annonce demandé n\'existe pas.');
        }
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $jobListingManager->incrementView($annonce, $request->getClientIp());
        $candidat = $params['candidat'];
        $currentUser = $params['currentUser'];
        $this->activityLogger->logJobLisitinViewActivity($params['currentUser'], $appExtension->generateJobReference($annonce->getId()));
        $applyJobPrice = $profileManager->getCreditAmount(Credit::ACTION_APPLY_JOB);
        [$applied, $application] = $jobListingManager->isAppliedByCandidate($annonce, $candidat);
        $form = $this->createForm(ApplicationsType::class, $application);
        $form->handleRequest($request);
        $params['annonce'] = $annonce;
        $params['applied'] = $applied;
        $params['show_recruiter_price'] = $profileManager->getCreditAmount(Credit::ACTION_VIEW_RECRUITER);
        $params['apply_job_price'] = $applyJobPrice;
        $params['form'] = $form->createView();
        if ($form->isSubmitted() && $form->isValid()) {
            $application = $form->getData();
            $message = 'Candidature envoyée';
            $success = true;
            $status = 'Succès';
        
            $response = $creditManager->adjustCredits($currentUser, $applyJobPrice, "Candidature annonce");
            
            if (isset($response['error'])) {
                $message = $response['error'];
                $success = false;
                $status = 'Echec';
            }

            if (isset($response['success'])) {
                $applicationManager->saveForm($form);
                $applicationManager->sendEmails($application);
            }

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/candidate/live.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'credit' => $currentUser->getCredit()->getTotal(),
                ]);
            }

            return $this->redirectToRoute('app_tableau_de_bord_candidat_mes_candidatures');
        }

        return $this->render('tableau_de_bord/candidat/view_job_offer.html.twig', $params);
    }

    #[Route('/detail-prestation/{prestation}', name: 'app_tableau_de_bord_candidat_view_prestation')]
    #[IsGranted(PrestationVoter::VIEW, subject: 'prestation')]
    public function viewPrestation(Request $request, Prestation $prestation, PrestationManager $prestationManager, AppExtension $appExtension, PrestationExtension $prestationExtension, ProfileManager $profileManager, Security $security): Response
    {
        if($security->isGranted(PrestationVoter::EDIT, null, $prestation)){
            if ($prestation === null || $prestation->getStatus() === Prestation::STATUS_DELETED || $prestation->getStatus() === Prestation::STATUS_PENDING) {
                throw $this->createNotFoundException('Nous sommes désolés, mais le prestation demandé n\'existe pas.');
            }
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

        return $this->render('tableau_de_bord/candidat/view_prestation.html.twig', $params);
    }

    #[Route('/modifier-une-prestation/{prestation}', name: 'app_tableau_de_bord_candidat_edition_prestation')]
    #[IsGranted(PrestationVoter::EDIT, subject: 'prestation')]
    public function editpresta(
        Request $request, 
        Prestation $prestation, 
        ProfileManager $profileManager,
    ): Response
    {
        $params = $this->getData();
        if ($params instanceof RedirectResponse) {
            return $params; 
        }
        $creditAmount = $profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
        $boostType = 'PRESTATION_CANDIDATE';
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => $boostType]);
        $form->handleRequest($request);
        $params['form'] = $form->createView();
        $params['creditAmount'] = $creditAmount;

        return $this->render('tableau_de_bord/candidat/creation_prestations.html.twig', $params);
    }

    public function getData()
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
        $data['boost'] = $candidat->getBoost();
        $data['boostVisibility'] = $candidat->getBoostVisibility();
        $data['action'] = $this->urlGenerator->generate('app_olona_talents_joblistings', []);
        $data['credit'] = $currentUser->getCredit()->getTotal();
        $data['notificationsCount'] = $this->em->getRepository(Notification::class)->countIsRead($currentUser,false);

        return $data;
    }
}
