<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Entity\User;
use App\Entity\Langue;
use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\Vues\AnnonceVues;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\Referrer\Referral;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Candidat\UploadCVType;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Candidate\CV;
use App\Entity\Finance\Employe;
use App\Entity\Moderateur\Assignation;
use App\Entity\Moderateur\TypeContrat;
use App\Form\Search\AnnonceSearchType;
use App\Form\Candidat\ApplicationsType;
use App\Form\Candidat\AvailabilityType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Profile\Candidat\StepThreeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\LangagesRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\AssignationRepository;
use App\Repository\Moderateur\TypeContratRepository;
use App\Form\Search\Annonce\CandidatAnnonceSearchType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Profile\Candidat\Edit\StepOneType as EditStepOneType;
use App\Form\Profile\Candidat\Edit\StepTwoType as EditStepTwoType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Form\Profile\Candidat\Edit\StepThreeType as EditStepThreeType;
use App\Service\PdfProcessor;

#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ProfileManager $profileManager,
        private CandidatManager $candidatManager,
        private JobListingRepository $jobListingRepository,
        private ApplicationsRepository $applicationsRepository,
        private AssignationRepository $assignationRepository,
        private LangagesRepository $langagesRepository,
        private TypeContratRepository $typeContratRepository,
        private RequestStack $requestStack,
        private FileUploader $fileUploader,
        private PdfProcessor $pdfProcessor,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }
    
    private function checkCandidat()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $entreprise = $user->getEntrepriseProfile();
        $employe = $user->getEmploye();
        if ($entreprise instanceof EntrepriseProfile) {
            // Si l'utilisateur n'a pas de profil candidat, on lance une exception
            throw new AccessDeniedException('Désolé, la page que vous souhaitez consulter est réservée aux profils candidats. Si vous possédez un tel profil, veuillez vous assurer que vous êtes connecté avec les identifiants appropriés.');
        }
        if($candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }
        if($employe instanceof Employe){
            return $this->redirectToRoute('app_profile');
        }
    
        return null;
    }

    public function createAvailabilityForm()
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $form = $this->createForm(AvailabilityType::class, $this->candidatManager->initAvailability($candidat));

        return $form;
    }

    public function availabilityFormView()
    {
        $form = $this->createAvailabilityForm();

        return $this->render('parts/_availability_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/submit-availability', name: 'submit_availability')]
    public function submitAvailability(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $form = $this->createAvailabilityForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $availability = $form->getData();
            if($availability->getNom() !== "from-date"){
                $availability->setDateFin(null);
            }

            // Enregistrer les modifications dans la base de données
            $this->em->persist($availability);
            $this->em->flush();
            // $this->candidatManager->sendNotificationEmail($candidat);
        }

        // Renvoyer l'utilisateur à l'URL d'où il est venu en cas d'échec
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_candidat_compte');
    }

    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->redirectToRoute('app_dashboard_candidat_annonce');

        $candidat = $user->getCandidateProfile();
        $now = new DateTime();

        $monday = clone $now;
        $monday->modify('this monday');
        $sunday = clone $monday;
        $sunday->modify('+6 days');

        $formatMonday = $monday->format('d');
        $formatSunday = $sunday->format('d F Y');

        $form = $this->createForm(AnnonceSearchType::class);
        $form->handleRequest($request);
        $data = $this->jobListingRepository->findAll();
        $annonces = $this->candidatManager->annoncesCandidatDefaut($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('query')->getData();
            // $typeContrat = $form->get('typeContrat')->getData();
            $data = $this->searchPostings($searchTerm, $this->em);
        }

        return $this->render('dashboard/candidat/index.html.twig', [
            'identity' => $candidat,
            'annonces' => $annonces,
            'formatMonday' => $formatMonday,
            'formatSunday' => $formatSunday,
            'form' => $form->createView(),
            'postings' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    private function searchPostings(string $query = null, EntityManagerInterface $entityManager): array
    {
        if (empty($query)) {
            return $this->jobListingRepository->findAllJobListingPublished();
        }

        $qb = $entityManager->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];

        $conditions = [];
        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(p.titre LIKE :query' . $key .
                ' OR p.description LIKE :query' . $key .
                ' OR sec.nom LIKE :query' . $key .
                ' OR lang.nom LIKE :query' . $key .
                ' OR ts.nom LIKE :query' . $key . ')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        // if (!empty($typeContrat)) {
        //     array_merge($parameters, ['typeContrat' => $typeContrat->getNom()]);
        // }

        $qb->select('p')
            ->from('App\Entity\Entreprise\JobListing', 'p')
            ->leftJoin('p.secteur', 'sec')
            ->leftJoin('p.competences', 'ts')
            ->leftJoin('p.langues', 'lang')
            ->where(implode(' AND ', $conditions))
            ->andWhere('p.status = :status')
            ->setParameters(array_merge($parameters, ['status' => JobListing::STATUS_PUBLISHED]));

        return $qb->getQuery()->getResult();
    }


    #[Route("/profil", name: "profil")]
    public function profil(): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        return $this->render('dashboard/candidat/profil.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface ): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }
        $typesContrat = $this->typeContratRepository->findAll();

        $form = $this->createForm(CandidatAnnonceSearchType::class, null, [
            'types_contrat' => $typesContrat,
        ]);

        $form->handleRequest($request);
        $data = $this->jobListingRepository->findAllJobListingPublished();
        // $annonces = $this->candidatManager->annoncesCandidatDefaut($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $typeContratObjet = $form->get('typeContrat')->getData();
            $typeContrat = $typeContratObjet ? $typeContratObjet->getNom() : null; 
            $lieu = $form->get('lieu')->getData();
            $competences = $form->get('competences')->getData();
            $data = $this->candidatManager->searchAnnonce($titre, $lieu, $typeContrat, $competences);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/candidat/annonces/_annonces.html.twig', [
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

        return $this->render('dashboard/candidat/annonces/annonces.html.twig', [
            'identity' => $candidat,
            'form' => $form->createView(),
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            // 'postings' => $data,
            'result' => $data,
        ]);
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_candidat_annonce_show')]
    public function showAnnonce(Request $request, JobListing $annonce): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $entreprise = $annonce->getEntreprise();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }
        $application = $this->applicationsRepository->findOneBy([
            'candidat' => $candidat,
            'annonce' => $annonce
        ]);
        $assignation = $this->assignationRepository->findOneBy([
            'profil' => $candidat,
            'jobListing' => $annonce
        ]);

        if(!$assignation instanceof Assignation){
            $applied = true;
            $montant = $candidat->getTarifCandidat() ? $candidat->getTarifCandidat()->getMontant() : 0;
            $assignation = new Assignation();
            $assignation->setDateAssignation(new DateTime());
            $assignation->setJobListing($annonce);
            $assignation->setRolePositionVisee(Assignation::TYPE_CANDIDAT);
            $assignation->setProfil($candidat);
            $assignation->setCommentaire("Candidature spontanée");
            $assignation->setForfait($montant); 
            $assignation->setStatus(Assignation::STATUS_PENDING);
        }

        $applied = false;

        if(!$application instanceof Applications){
            $applied = true;
            $application = new Applications();
            $application->setDateCandidature(new DateTime());
            $application->setAnnonce($annonce);
            $application->setCvLink($candidat->getCv());
            $application->setCandidat($candidat);
            $application->setAssignation($assignation);
            $application->setStatus(Applications::STATUS_PENDING);
        }
        $form = $this->createForm(ApplicationsType::class, $application);
        $form->handleRequest($request);
        $formUpload = $this->createForm(UploadCVType::class, $candidat);
        $formUpload->handleRequest($request);

        if ($formUpload->isSubmitted() && $formUpload->isValid()) {
            $cvFile = $formUpload->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $candidat);
            }
            $this->em->persist($candidat);
            $this->em->flush();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action');
            $application = $form->getData();
            if ($action === 'quick_apply') {
                // Logique pour la soumission rapide
                $application->setLettreMotication(
                    "Candidature Flash"
                );
            } elseif ($action === 'custom_apply') {
                // Logique pour la soumission personnalisée
                // Utiliser le CV personnalisé et/ou la lettre de motivation fournis
            }
            $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $user->getEmail()]);
            if($refered instanceof Referral){
                $refered->setStep(4);
                $this->em->persist($refered);
            }
            $this->em->persist($assignation);
            $this->em->persist($application);
            $this->em->flush();
    
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Une nouvelle candidature sur Olona Talents",
                "moderateur/notification_candidature.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'candidature' => $application,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
    
            /** Envoi email candidat */
            $this->mailerService->send(
                $user->getEmail(),
                "Votre candidature a été prise en compte sur Olona Talents",
                "candidat/notification_candidature.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'candidature' => $application,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_annonces', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
    
            /** Envoi email entreprise */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                "Nouvelle candidature reçue sur votre annonce Olona-talents.com",
                "entreprise/notification_candidature.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'candidature' => $application,
                    'candidat' => $candidat,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_annonce_view_default', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            $this->addFlash('success', "Candidature envoyé ");


            return $this->redirectToRoute('app_dashboard_candidat_annonce');
        }

        if ($annonce) {
            $ipAddress = $request->getClientIp();
            $viewRepository = $this->em->getRepository(AnnonceVues::class);
            $existingView = $viewRepository->findOneBy([
                'annonce' => $annonce,
                'idAdress' => $ipAddress,
            ]);
    
            if (!$existingView) {
                $view = new AnnonceVues();
                $view->setAnnonce($annonce);
                $view->setIdAdress($ipAddress);
    
                $this->em->persist($view);
                $annonce->addAnnonceVue($view);
                $this->em->flush();
            }
        }

        return $this->render('dashboard/candidat/annonces/show.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
            'applied' => $applied,
            'form' => $form->createView(),
            'formUpload' => $formUpload->createView(),
        ]);
    }

    #[Route('/annonce/{jobId}/details', name: 'app_dashboard_candidat_annonce_details')]
    public function detailsAnnonce(Request $request, JobListing $annonce): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('dashboard/candidat/annonces/details.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
        ]);
    }

    #[Route('/all/annonces', name: 'app_dashboard_candidat_annonces')]
    public function allAnnonces(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('dashboard/candidat/candidature/index.html.twig', [
            'pendings' => $paginatorInterface->paginate(
                $this->candidatManager->getPendingApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'randezvous' => $paginatorInterface->paginate(
                $this->candidatManager->getMettingApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'accepteds' => $paginatorInterface->paginate(
                $this->candidatManager->getAcceptedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'refuseds' => $paginatorInterface->paginate(
                $this->candidatManager->getRefusedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'archiveds' => $paginatorInterface->paginate(
                $this->candidatManager->getArchivedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/compte', name: 'app_dashboard_candidat_compte')]
    public function compte(Request $request): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_profile');
        }

        $formOne = $this->createForm(EditStepOneType::class, $candidat);
        $formTwo = $this->createForm(EditStepTwoType::class, $candidat);
        $formThree = $this->createForm(EditStepThreeType::class, $candidat);
        $formOne->handleRequest($request);
        $formTwo->handleRequest($request);
        $formThree->handleRequest($request);

        if ($formOne->isSubmitted() && $formOne->isValid()) {
            $this->em->persist($candidat);
            $this->em->flush();
            $this->candidatManager->sendNotificationEmail($candidat); 
        }

        if ($formTwo->isSubmitted() && $formTwo->isValid()) {
            $this->em->persist($candidat);
            $this->em->flush();
            $this->candidatManager->sendNotificationEmail($candidat); 
        }

        if ($formThree->isSubmitted() && $formThree->isValid()) {
            $cvFile = $formThree->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);

                // Process the PDF with Tesseract and store the response
                $pdfPath = $this->fileUploader->getTargetDirectory() . '/' . $fileName[0];
                $this->pdfProcessor->processPdf($pdfPath, $candidat);
                $this->profileManager->saveCV($fileName, $candidat);
            }
            $this->em->persist($candidat);
            $this->em->flush();
            $this->candidatManager->sendNotificationEmail($candidat); 
        }

        return $this->render('dashboard/candidat/compte.html.twig', [
            'form_one' => $formOne->createView(),
            'form_two' => $formTwo->createView(),
            'form_three' => $formThree->createView(),
            'candidat' => $candidat,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }

    #[Route('/delete/{cvId}', name: 'app_delete_cv')]
    public function deleteEditedCV(Request $request, int $cvId): Response
    {
        $cvEdited = $this->em->getRepository(CV::class)->find($cvId);

        if ($cvEdited !== null) {
            $candidat = $cvEdited->getCandidat();
            $candidat->setCv(null);
            $this->em->persist($candidat);
            $this->em->remove($cvEdited);
            $this->em->flush();
        }


        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_candidat_compte');
    }

    #[Route('/guides/astuce', name: 'app_dashboard_guides_astuce')]
    public function astuce(): Response
    {
        $this->checkCandidat();
        
        return $this->render('dashboard/candidat/guides/astuce.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/lettre-de-motivation', name: 'app_dashboard_guides_motivation')]
    public function motivation(): Response
    {
        $this->checkCandidat();
        
        return $this->render('dashboard/candidat/guides/motivation.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/cv', name: 'app_dashboard_guides_cv')]
    public function cv(): Response
    {
        $this->checkCandidat();
        return $this->render('dashboard/candidat/guides/cv.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/reseautage', name: 'app_dashboard_guides_reseautage')]
    public function reseautage(): Response
    {
        $this->checkCandidat();
        return $this->render('dashboard/candidat/guides/reseautage.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

}
