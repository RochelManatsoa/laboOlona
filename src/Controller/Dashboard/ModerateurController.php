<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Entity\User;
use App\Twig\AppExtension;
use App\Entity\Candidate\CV;
use App\Entity\Notification;
use App\Service\FileUploader;
use App\Service\OpenAIResume;
use App\Service\PdfProcessor;
use App\Manager\OpenaiManager;
use App\Entity\Finance\Employe;
use App\Manager\ProfileManager;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Referrer\Referral;
use App\Service\OpenAITranslator;
use App\Service\User\UserService;
use App\Entity\Finance\Simulateur;
use App\Entity\Moderateur\Metting;
use App\Manager\ModerateurManager;
use App\Entity\Moderateur\EditedCv;
use App\Manager\AssignationManager;
use App\Form\Moderateur\MettingType;
use App\Manager\NotificationManager;
use App\Entity\Entreprise\JobListing;
use App\Entity\Moderateur\Invitation;
use App\Form\Moderateur\CandidatType;
use App\Form\Moderateur\EditedCvType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\Assignation;
use App\Form\Candidat\AvailabilityType;
use App\Form\Moderateur\InvitationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Manager\Referrer\ReferenceManager;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Moderateur\AssignationFormType;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use App\Repository\Referrer\ReferralRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use App\Repository\Finance\SimulateurRepository;
use App\Repository\Moderateur\MettingRepository;
use App\Form\Moderateur\AssignateProfileFormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\InvitationRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\AssignationRepository;
use App\Repository\Moderateur\TypeContratRepository;
use App\Repository\BusinessModel\TransactionRepository;
use App\Form\Search\Candidat\ModerateurCandidatSearchType;
use App\Form\Moderateur\EntrepriseType as NewEntrepriseType;
use App\Form\Search\Entreprise\ModerateurEntrepriseSearchType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Search\Annonce\ModerateurAnnonceEntrepriseSearchType;
use App\Repository\PrestationRepository;

#[Route('/dashboard/moderateur')]
class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private OpenaiManager $openaiManager,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private ProfileManager $profileManager,
        private NotificationManager $notificationManager,
        private SecteurRepository $secteurRepository,
        private TypeContratRepository $typeContratRepository,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private MettingRepository $mettingRepository,
        private NotificationRepository $notificationRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private FileUploader $fileUploader,
        private PaginatorInterface $paginator,
        private ApplicationsRepository $applicationsRepository,
        private AssignationRepository $assignationRepository,
        private InvitationRepository $invitationRepository,
        private SimulateurRepository $simulateurRepository,
        private TransactionRepository $transactionRepository,
        private PrestationRepository $prestationRepository,
        private AssignationManager $assignationManager,
        private PdfProcessor $pdfProcessor,
        private OpenAITranslator $openAITranslator,
        private AppExtension $appExtension,
        private ReferenceManager $referenceManager,
    ) {}

    #[Route('/', name: 'app_dashboard_moderateur')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/moderateur/index.html.twig', [
            'secteurs' => $this->secteurRepository->countAll(),
            'simulations' => $this->simulateurRepository->countAll(),
            'typeContrats' => $this->typeContratRepository->countAll(),
            'annonces' => $this->jobListingRepository->countAll(),
            'annonces_pending' => $this->jobListingRepository->countPending(),
            'entreprises' => $this->entrepriseProfileRepository->countAll(),
            'candidats' => $this->candidateProfileRepository->countAll(),
            'candidats_pending' => $this->candidateProfileRepository->countPending(),
            'prestations' => $this->prestationRepository->countAll(),
            'prestations_new' => $this->prestationRepository->countPending(),
            'candidatures' => $this->applicationsRepository->countAll(),
            'candidatures_new' => $this->applicationsRepository->countPending(),
            'invitations' => $this->invitationRepository->countAll(),
            'transactions' => $this->transactionRepository->countAll(),
            'transactions_new' => $this->transactionRepository->countPending(),
        ]);
    }

    #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
    public function entreprises(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** Formulaire nouvelle entreprise */
        $newEntreprise = new EntrepriseProfile();
        $formEntreprise = $this->createForm(NewEntrepriseType::class, $newEntreprise);
        $formEntreprise->handleRequest($request);
        if($formEntreprise->isSubmitted() && $formEntreprise->isValid()){
            $entrepriseProfile = $formEntreprise->getData();
            $user = $entrepriseProfile->getEntreprise();
            $user->setType(User::ACCOUNT_ENTREPRISE);
            $user->setDateInscription(new DateTime());
            $entrepriseProfile->setStatus(EntrepriseProfile::STATUS_PENDING);
    
            $this->em->persist($user);
            $this->em->persist($entrepriseProfile);
            $this->em->flush();

            $this->addFlash('success', 'Entreprise créé avec succès');
        }
        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllEntreprise();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $secteur = $form->get('secteur')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchEntreprise($nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_entreprises.html.twig', [
                        'entreprises' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/entreprise/index.html.twig', [
            'entreprises' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
            'formEntreprise' => $formEntreprise->createView(),
        ]);
    }

    #[Route('/entreprise/{id}', name: 'voir_entreprise')]
    public function voirEntreprise(EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        return $this->render('dashboard/moderateur/entreprise_view.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/supprimer/entreprise/{id}', name: 'supprimer_entreprise', methods: ['POST'])]
    public function supprimerEntreprise(Request $request, EntityManagerInterface $entityManager, EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        if ($this->isCsrfTokenValid('delete' . $entreprise->getId(), $request->request->get('_token'))) {
            $employe = $entityManager->getRepository(Employe::class)->findOneBy(['user' => $entreprise->getEntreprise()]);
            $simulateurs = $entityManager->getRepository(Simulateur::class)->findBy(['employe' => $employe]);
            foreach ($simulateurs as $simulateur) {
                $employe->removeSimulateur($simulateur);
            }
            $invitations = $entityManager->getRepository(Invitation::class)->findBy(['reader' => $entreprise->getEntreprise()->getId()]);
            foreach ($invitations as $invitation) {
                $invitation->setReader(null);
                $entityManager->persist($invitation);
            }
            $entityManager->flush();
            $jobListings = $entityManager->getRepository(JobListing::class)->findBy(['entreprise' => $entreprise->getId()]);
            foreach ($jobListings as $jobListing) {
                $entityManager->remove($jobListing);
            }
            $entityManager->flush();
            // Ensuite, supprimer l'entreprise
            $entityManager->remove($entreprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_entreprises');
    }

    #[Route('/entreprises/{id}/annonces', name: 'app_dashboard_moderateur_entreprises_annonces')]
    public function entreprisesAnnonces(Request $request, PaginatorInterface $paginatorInterface, EntrepriseProfile $entreprise, JobListingRepository $jobListingRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->query->get('status');

        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurAnnonceEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, null, null, $status);
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $status = $form->get('status')->getData();
            $secteur = $form->get('secteur')->getData();
            $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, $nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        $annonces = $jobListingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/moderateur/entreprise/annonces.html.twig', [
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{entreprise_id}/annonce/{annonce_id}/status', name: 'change_status_annonce_entreprise', methods: ['POST'])]
    public function changeEntrepriseAnnonceStatus(Request $request, EntityManagerInterface $entityManager, int $entreprise_id, int $annonce_id, JobListingRepository $jobListingRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->request->get('status');
        $entreprise = $entityManager->getRepository(EntrepriseProfile::class)->find($entreprise_id);
        $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
        }

        if ($status && in_array($status, JobListing::getArrayStatuses())) {
            $annonce->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
    }

    #[Route('/entreprises/{entreprise_id}/annonces/{annonce_id}/view', name: 'app_dashboard_moderateur_entreprises_annonces_view')]
    public function entreprisesAnnoncesView(int $entreprise_id, int $annonce_id, EntrepriseProfileRepository $entrepriseProfileRepository, JobListingRepository $jobListingRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $entreprise = $entrepriseProfileRepository->find($entreprise_id);
        $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);

        if (!$entreprise || !$annonce) {
            throw $this->createNotFoundException('L\'entreprise ou l\'annonce n\'a pas été trouvée');
        }

        return $this->render('dashboard/moderateur/entreprises_annonces_view.html.twig', [
            'entreprise' => $entreprise,
            'annonce' => $annonce,
        ]);
    }

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    public function candidats(
        Request $request, 
        PaginatorInterface $paginatorInterface, 
    ): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->query->get('status');

        /** Formulaire nouveau candidat */
        $newCandidate = new CandidateProfile();
        $formCandidate = $this->createForm(CandidatType::class, $newCandidate);
        $formCandidate->handleRequest($request);
        if($formCandidate->isSubmitted() && $formCandidate->isValid()){
            $candidateProfile = $formCandidate->getData();
            $user = $candidateProfile->getCandidat();
            $user->setType(User::ACCOUNT_CANDIDAT);
            $user->setDateInscription(new DateTime());
            $candidateProfile->setIsValid(false);
            $candidateProfile->setStatus(CandidateProfile::STATUS_PENDING);
            $candidateProfile->setUid(new Uuid(Uuid::v1()));

            $cvFile = $formCandidate->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidateProfile);
                $candidateProfile->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $candidateProfile);
            }
    
            $this->em->persist($user);
            $this->em->persist($candidateProfile);
            $this->em->flush();

            $this->addFlash('success', 'Candidat créé avec succès');
        }

        /** Formulaire de recherche candidat */
        $form = $this->createForm(ModerateurCandidatSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->searchCandidat(null, null, $status, null);
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $titre = $form->get('titre')->getData();
            $status = $form->get('status')->getData();
            $availability = $form->get('availability')->getData();
            $data = $this->moderateurManager->searchCandidat($nom, $titre, $status, $availability);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/candidat/_candidats.html.twig', [
                        'candidats' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/candidat/index.html.twig', [
            'candidats' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'form' => $form->createView(),
            'formCandidate' => $formCandidate->createView(),
            'result' => $data
        ]);
    }

    #[Route('/candidat/{uid}/status', name: 'change_status_candidat', methods: ['POST'])]
    public function changeCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->request->get('status');
        if ($status && in_array($status, ['PENDING', 'BANNISHED', 'VALID', 'FEATURED', 'RESERVED'])) {
            $candidateProfile->setStatus($status);
            $candidateProfile->setUpdatedAt(new DateTime());
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
                $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $candidateProfile->getCandidat()->getEmail()]);
                if($refered instanceof Referral){
                    $refered->setStep(3);
                    $this->em->persist($refered);
                    $this->em->flush();
                }

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat');
    }

    #[Route('/candidat/{uid}/certification', name: 'change_status_certification_candidat', methods: ['POST'])]
    public function changeCertificationCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->request->get('status');
        if ($status && in_array($status, ['0', '1'])) {
            $candidateProfile->setIsValid($status);
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_profile_candidat');
    }


    #[Route('/candidats/{id}', name: 'app_dashboard_moderateur_candidat_view')]
    public function viewCandidat(Request $request, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        $assignation = $this->assignationManager->init();
        $assignation->setProfil($candidat);
        $assignation->setRolePositionVisee(Assignation::TYPE_OLONA);
        $assignation->setStatus(Assignation::STATUS_PENDING);
        $formAssignation = $this->createForm(AssignationFormType::class, $assignation);
        $formAssignation->handleRequest($request);
        if($formAssignation->isSubmitted() && $formAssignation->isValid()){
            if($assignation->getJobListing()->getStatus() !== JobListing::STATUS_PUBLISHED){
                $this->addFlash('danger', 'Vous devez publier l\'annonce avant de faire une assignation');                
                return $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view', ['id' => $candidat->getId()]);
            }

            /** Send Notification */
            $titre = 'Réponse à votre demande de devis';
            $titreMod = 'Réponse à la demande de devis de '.$assignation->getJobListing()->getEntreprise()->getNom().' pour '.$this->appExtension->generatePseudo($assignation->getProfil());
            $contenu = '             
                <p>Nous vous remercions pour votre demande de devis concernant le candidat '.$this->appExtension->generatePseudo($assignation->getProfil()).'. Nous sommes heureux de vous informer que nous avons préparé une proposition adaptée à vos besoins.</p>
                <p>Voici les détails de notre offre :</p>
                <ul>
                    <li>Prix estimatif : '.$assignation->getForfait().' €</li>
                    <li>Conditions spécifiques : '.$assignation->getCommentaire().'</li>
                </ul>
                <p>Nous espérons que notre proposition vous conviendra et restons à votre disposition pour toute modification ou précision supplémentaire.</p>
                <p>Nous sommes impatients de travailler avec vous et de contribuer au succès de votre projet.</p>
                <p>Cordialement,</p>
            ';
            $contenuMod = ' 
            <p>Voici les détails de l\'offre :</p>
                <ul>
                    <li>Prix estimatif : '.$assignation->getForfait().' €</li>
                    <li>Conditions spécifiques : '.$assignation->getCommentaire().'</li>
                </ul>
            ';
            $this->notificationManager->notifyModerateurs($assignation->getProfil()->getCandidat(), Notification::TYPE_CONTACT, $titreMod, $contenuMod );
            $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $assignation->getJobListing()->getEntreprise()->getEntreprise(), Notification::TYPE_CONTACT, $titre, $contenu );
            $assignation = $formAssignation->getData();
            $this->assignationManager->saveForm($formAssignation);

    
            /** Envoi email entreprise */
            $this->mailerService->send(
                $assignation->getJobListing()->getEntreprise()->getEntreprise()->getEmail(),
                "Suggestion de profil pour votre annonce sur Olona Talents",
                "entreprise/notification_assignation.html.twig",
                [
                    'user' => $assignation->getJobListing()->getEntreprise()->getEntreprise(),
                    'candidature' => $assignation,
                    'candidat' => $candidat,
                    'objet' => "mise à jour",
                    'details_annonce' => $assignation->getJobListing(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_annonce_view_suggest', ['id' => $assignation->getJobListing()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Assignation effectuée');
                
            return $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view', ['id' => $candidat->getId()]);
        }

        /**
         * New process upload cv
         */
        $cvForms = [];
        $cvs = $this->em->getRepository(CV::class)->findby([
            'candidat' => $candidat
        ], ['id' => 'DESC']);

        foreach ($cvs as $key => $cv) {
            $formName = 'cv_form_'.$cv->getId();
            $cvForm = $this->createForm(EditedCvType::class, $cv, [
                'form_id' => $formName
            ]);
            $cvForm->handleRequest($request);
            $cvForms[$cv->getId()] = [
                'form' => $cvForm->createView(),
                'formName' => $formName
            ];
        }
        /**
         * End new process 
         */

        $editedCv = new EditedCv();
        $editedCv->setUploadedAt(new DateTime());
        $editedCv->setCandidat($candidat);
        $formCv = $this->createForm(EditedCvType::class, $editedCv);
        $formCv->handleRequest($request);
        if($formCv->isSubmitted() && $formCv->isValid()){
            $cvFile = $formCv->get('cvEdit')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->uploadEditedCv($cvFile, $candidat);
                $this->profileManager->saveCVEdited($fileName, $candidat, $cvFile);
            }
            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view');
        }

        $formDispo = $this->createForm(AvailabilityType::class, $this->candidatManager->initAvailability($candidat));
        $formDispo->handleRequest($request);
        if ($formDispo->isSubmitted() && $formDispo->isValid()) {
            $availability = $formDispo->getData();
            if($availability->getNom() !== "from-date"){
                $availability->setDateFin(null);
            }
            $this->em->persist($availability);
            $this->em->flush();

            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view');
        }

        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($candidat->getCandidat());
        $notification->setType(Notification::TYPE_PROFIL);
        $notification->setIsRead(false);
        $notification->setTitre("Information sur votre profil Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour [Nom de l'Utilisateur],</p><p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p><p>Pour assurer l'efficacité et la qualité de nos services, il est important que chaque profil soit complet et à jour. Voici les informations manquantes :</p><ol><li>[Information manquante 1]</li><li>[Information manquante 2]</li><li>[Autres informations manquantes, si nécessaire]</li></ol><p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p><p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p><p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>
            "
        );

        $form = $this->createForm(NotificationProfileType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'utilisateur */
            $this->mailerService->send(
                $candidat->getCandidat()->getEmail(),
                $notification->getTitre(),
                "moderateur/notification_profile.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_compte', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }

        return $this->render('dashboard/moderateur/candidat_view.html.twig', [
            'candidat' => $candidat,
            'form' => $form->createView(),
            'formDispo' => $formDispo->createView(),
            'formCv' => $formCv->createView(),
            'cvForms' => $cvForms, 
            'cvs' => $cvs, 
            'formAssignation' => $formAssignation->createView(),
            'notifications' => $this->em->getRepository(Notification::class)->findBy([
                'destinataire' => $candidat->getCandidat()
            ], ['id' => 'DESC']),
        ]);
    }

    #[Route('/candidats/{id}/applications', name: 'app_dashboard_moderateur_candidat_applications')]
    public function candidatApplications(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $applications = $applicationsRepository->findBy(['candidat' => $id]);

        return $this->render('dashboard/moderateur/candidat_applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/status/application/{id}', name: 'change_status_application', methods: ['POST'])]
    public function changeApplicationStatus(Request $request, EntityManagerInterface $entityManager, Applications $application): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $status = $request->request->get('status');
        if ($status && in_array($status, Applications::getArrayStatuses())) {
            $application->setStatus($status);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');

            /** Envoi mail candidat */
            $this->mailerService->send(
                $application->getCandidat()->getCandidat()->getEmail(),
                "Statut de votre candidature sur Olona Talents",
                "candidat/status_candidature.html.twig",
                [
                    'user' => $application->getCandidat()->getCandidat(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_annonces', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Envoi mail entreprise */
            $this->mailerService->send(
                $application->getAnnonce()->getEntreprise()->getEntreprise()->getEmail(),
                "Une candidature a été déposée sur votre annonce sur Olona Talents",
                "entreprise/status_candidature.html.twig",
                [
                    'user' => $application->getAnnonce()->getEntreprise()->getEntreprise(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_candidatures', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Envoi mail moderateurs */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Une nouvelle candidature a été déposée pour une annonce sur Olona Talents",
                "moderateur/status_candidature.html.twig",
                [
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidat_applications', ['id' => $application->getCandidat()->getId()]);
    }

    #[Route('/candidats/{id}/applications/en-attente', name: 'app_dashboard_moderateur_candidat_applications_en_attente')]
    public function candidatApplicationsEnAttente(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'PENDING']);

        return $this->render('dashboard/moderateur/candidat_applications_en_attente.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/acceptees', name: 'app_dashboard_moderateur_candidat_applications_acceptees')]
    public function candidatApplicationsAcceptees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'ACCEPTED']);

        return $this->render('dashboard/moderateur/candidat_applications_acceptees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/refusees', name: 'app_dashboard_moderateur_candidat_applications_refusees')]
    public function candidatApplicationsRefusees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'REFUSED']);

        return $this->render('dashboard/moderateur/candidat_applications_refusees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/competences', name: 'app_dashboard_moderateur_candidat_competences')]
    public function candidatCompetences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        return $this->render('dashboard/moderateur/candidat_competences.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidats/{id}/experiences', name: 'app_dashboard_moderateur_candidat_experiences')]
    public function candidatExperiences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        return $this->render('dashboard/moderateur/candidat_experiences.html.twig', [
            'candidat' => $candidat,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
        ]);
    }


    #[Route('/supprimer/candidat/{id}', name: 'supprimer_candidat', methods: ['POST'])]
    public function supprimerCandidat(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        if ($this->isCsrfTokenValid('delete' . $candidat->getId(), $request->request->get('_token'))) {
            $employe = $entityManager->getRepository(Employe::class)->findOneBy(['user' => $candidat->getCandidat()]);
            if($employe instanceof Employe){
                $simulateurs = $entityManager->getRepository(Simulateur::class)->findBy(['employe' => $employe]);
                foreach ($simulateurs as $simulateur) {
                    $employe->removeSimulateur($simulateur);
                }
                $entityManager->remove($employe);
                $entityManager->flush();
            }
            $invitations = $entityManager->getRepository(Invitation::class)->findBy(['reader' => $candidat->getCandidat()->getId()]);
            foreach ($invitations as $invitation) {
                $invitation->setReader(null);
                $entityManager->persist($invitation);
            }
            $entityManager->flush();
            // $jobListings = $entityManager->getRepository(JobListing::class)->findBy(['entreprise' => $entreprise->getId()]);
            // foreach ($jobListings as $jobListing) {
            //     $entityManager->remove($jobListing);
            // }
            // $entityManager->flush();
            // Ensuite, supprimer l'entreprise
            $entityManager->remove($candidat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_profile_candidat');
    }

    #[Route('/mettings', name: 'app_dashboard_moderateur_mettings')]
    public function mettings(Request $request, MettingRepository $mettingRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = $mettingRepository->findBy([], [
            'id' => 'DESC'
        ]);

        return $this->render('dashboard/moderateur/conference/index.html.twig', [
            'mettings' => $this->paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/metting/show/{id}', name: 'app_dashboard_moderateur_metting_show', methods: ['GET'])]
    public function show(Metting $metting): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        return $this->render('dashboard/moderateur/conference/show.html.twig', compact('metting'));
    }

    #[Route('/metting/new', name: 'app_dashboard_moderateur_metting_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $metting = new Metting();
        $customId = new Uuid(Uuid::v4());
        $metting->setCustomId($customId);
        $metting->setLink($this->urlGenerator->generate(
            'app_dashboard_conference',
            [
                'uuid' => $customId
            ], 
            UrlGeneratorInterface::ABSOLUTE_URL
        ));
        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($metting);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->render('dashboard/moderateur/conference/new.html.twig', [
            'metting' => $metting,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/metting/{id}/edit', name: 'app_dashboard_moderateur_metting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->render('dashboard/moderateur/conference/edit.html.twig', [
            'metting' => $metting,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/metting/{id}', name: 'app_dashboard_moderateur_metting_delete', methods: ['POST'])]
    public function delete(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        if ($this->isCsrfTokenValid('delete' . $metting->getId(), $request->request->get('_token'))) {
            $entityManager->remove($metting);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_mettings');
    }


    #[Route('/notifications', name: 'app_dashboard_moderateur_notifications')]
    public function notifications(Request $request, NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }

    #[Route('/assignation/{profilId}', name: 'app_assignation')]
    public function assignate(Request $request, int $profilId): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $profil = $this->candidateProfileRepository->find($profilId);
        $assignationForm = $this->createForm(AssignateProfileFormType::class, $profil);
        
        $assignationForm->handleRequest($request);

        if ($assignationForm->isSubmitted() && $assignationForm->isValid()) {
            // Traitez l'assignation ici
            $assignation = $assignationForm->getData();
            $this->em->persist($assignation);
            $this->em->flush();
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_assignation');
    }

    #[Route('/cooptation', name: 'app_dashboard_moderateur_cooptation')]
    public function cooptation(Request $request, ReferralRepository $referralRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = $this->referenceManager->getReferenceAnnonce($referralRepository->findAll([
            'id' => 'DESC'
        ]));
        
        return $this->render('dashboard/moderateur/cooptation/index.html.twig', [
            'cooptations' => $this->paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/coopteurs', name: 'app_dashboard_moderateur_coopteurs')]
    public function coopteurs(Request $request, ReferrerProfileRepository $referrerProfileRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = $referrerProfileRepository->findAll([
            'id' => 'DESC'
        ]);
        
        return $this->render('dashboard/moderateur/coopteur/index.html.twig', [
            'coopteurs' => $this->paginator->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/cv/edit/{cvId}', name: 'app_edit_cv')]
    public function editCV(Request $request, int $cvId): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $cv = $this->em->getRepository(CV::class)->find($cvId);
        $cvEditedFrom = $this->createForm(EditedCvType::class, $cv->getEdited());
        
        $cvEditedFrom->handleRequest($request);

        if ($cvEditedFrom->isSubmitted() && $cvEditedFrom->isValid()) {
            $cvFile = $cvEditedFrom->get('cvEdit')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->uploadEditedCv($cvFile, $cv->getCandidat());
                $this->profileManager->saveCVEdited($fileName, $cv->getCandidat(), $cv);
                $this->em->getConnection()->close();
            }
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view', ['id' => $cv->getCandidat()->getId()]);
    }

    #[Route('/delete/edited/{cvEditedId}', name: 'app_delete_edited_cv')]
    public function deleteEditedCV(Request $request, int $cvEditedId): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $cvEdited = $this->em->getRepository(EditedCv::class)->find($cvEditedId);
        /**
         * @var User $user
         */
        $user = $this->userService->getCurrentUser();
        if ($cvEdited !== null) {
            $cV = $cvEdited->getCV(); 

            if ($cV !== null) {
                // Rompre la relation
                $cV->setEdited(null);
                $this->em->persist($cV);
            }

            $this->em->remove($cvEdited);
            $this->em->flush();
        }


        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat_view', ['id' => $user->getCandidateProfile()->getId()]);
    }
}
