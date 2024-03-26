<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Twig\AppExtension;
use App\Entity\Notification;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Data\SearchCandidateData;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Entity\Finance\Simulateur;
use App\Manager\EntrepriseManager;
use App\Manager\ModerateurManager;
use App\Form\Entreprise\AnnonceType;
use App\Manager\NotificationManager;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use Symfony\Component\Intl\Countries;
use App\Entity\Moderateur\Assignation;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Search\SearchCandidateTypeCopy;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\FavorisRepository;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\TypeContratRepository;
use App\Form\Search\Annonce\EntrepriseAnnonceSearchType;
use App\Manager\SimulateurEntrepriseManager;
use App\Repository\Finance\SimulateurRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/dashboard/entreprise')]
class EntrepriseController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private CandidatManager $candidatManager,
        private ModerateurManager $moderateurManager,
        private EntrepriseManager $entrepriseManager,
        private RequestStack $requestStack,
        private ApplicationsRepository $applicationRepository,
        private TypeContratRepository $typeContratRepository,
        private MettingRepository $mettingRepository,
        private UrlGeneratorInterface $urlGenerator,
        private NotificationManager $notificationManager,
        private AppExtension $appExtension,
        private FavorisRepository $favorisRepository,
        private SimulateurRepository $simulateurRepository,
        private SimulateurEntrepriseManager $simulateurEntrepriseManager,
    ) {
    }
    
    private function checkEntreprise()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getEntrepriseProfile();
        if (!$candidat instanceof EntrepriseProfile) {
            // Si l'utilisateur n'a pas de profil candidat, on lance une exception
            throw new AccessDeniedException('Désolé, la page que vous souhaitez consulter est réservée aux profils entreprise. Si vous possédez un tel profil, veuillez vous assurer que vous êtes connecté avec les identifiants appropriés.');
        }
    
        return null;
    }

    #[Route('/', name: 'app_dashboard_entreprise')]
    public function index(JobListingRepository $jobListingRepository, ApplicationsRepository $applicationRepository, MettingRepository $mettingRepository): Response
    {
        $this->checkEntreprise();

        return $this->redirectToRoute('app_dashboard_entreprise_annonces');

        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/index.html.twig', [
            'job_listings' => $jobListingRepository->findBy(['entreprise' => $entreprise]),
            'applications' => $applicationRepository->findBy(['jobListing' => $entreprise]),
            'meetings' => $mettingRepository->findBy(['entreprise' => $entreprise]),
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_entreprise_annonces')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface, JobListingRepository $jobListingRepository): Response
    {
        $this->checkEntreprise();

        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $typesContrat = $this->typeContratRepository->findAll();
        $job_listings = $jobListingRepository->findBy(['entreprise' => $entreprise]);
        $form = $this->createForm(EntrepriseAnnonceSearchType::class, null, [
            'types_contrat' => $typesContrat,
        ]);
        $form->handleRequest($request);
        $data = $this->entrepriseManager->findAllAnnonces();
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $status = $form->get('status')->getData();
            $typeContratObjet = $form->get('typeContrat')->getData();
            $typeContrat = $typeContratObjet ? $typeContratObjet->getNom() : null; 
            $salaire = $form->get('salaire')->getData();
            $data = $this->entrepriseManager->findAllAnnonces($titre, $status, $typeContrat, $salaire);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/entreprise/annonce/_annonces.html.twig', [
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


        return $this->render('dashboard/entreprise/annonce/index.html.twig', [
            'job_listings' => $jobListingRepository->findBy(['entreprise' => $entreprise]),
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'applications' => $entreprise->getAllApplications(),
            'meetings' => $this->mettingRepository->findBy(['entreprise' => $entreprise]),
            'form' => $form->createView()
        ]);
    }

    #[Route('/annonce/new', name: 'app_dashboard_entreprise_new_annonce')]
    public function newAnnonce(Request $request): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $jobListing = new JobListing();
        $jobListing->setEntreprise($entreprise); // suppose que l'utilisateur connecté est une entreprise
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setJobId(new Uuid(Uuid::v1()));
        $jobListing->setStatus(JobListing::STATUS_PENDING);

        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($jobListing);
            $this->em->flush();
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Nouvelle annonce sur Olona Talents",
                "moderateur/notification_annonce.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'objet' => "ajoutée",
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            /** Envoi email entreprise apres avoir publier une annonce */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                "Votre soumission d'annonce sur Olona Talents a été prise en compte",
                "entreprise/notification_annonce.html.twig",
                [
                    'user' => $entreprise,
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_view_annonce', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Annonce créée avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_annonces');
        }

        return $this->render('dashboard/entreprise/annonce/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}/edit', name: 'app_dashboard_entreprise_edit_annonce')]
    public function editAnnonce(Request $request, JobListing $jobListing): Response
    {
        $this->checkEntreprise();

        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing = $form->getData();
            $jobListing->setStatus(JobListing::STATUS_PENDING);
            $this->em->persist($jobListing);
            $this->em->flush();
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Mis à jour d'une annonce sur Olona Talents",
                "moderateur/notification_annonce.html.twig",
                [
                    'user' => $jobListing->getEntreprise()->getEntreprise(),
                    'objet' => "mise à jour",
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Annonce modifiée avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_annonces');
        }

        return $this->render('dashboard/entreprise/annonce/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}/delete', name: 'app_dashboard_entreprise_delete_annonce')]
    public function deleteAnnonce(Request $request, JobListing $jobListing): Response
    {
        $this->checkEntreprise();
        if ($this->isCsrfTokenValid('delete'.$jobListing->getId(), $request->request->get('_token'))) {
            $this->em->remove($jobListing);
            $this->em->flush();
            $this->addFlash('success', 'Annonce supprimée.');
        }

        return $this->redirectToRoute('app_dashboard_entreprise_annonces');
    }

    #[Route('/annonce/{id}/view', name: 'app_dashboard_entreprise_view_annonce')]
    public function viewAnnonce(Request $request, JobListing $jobListing): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/entreprise/annonce/view.html.twig', [
            'annonce' => $jobListing,
            'entreprise' => $user->getEntrepriseProfile(),
        ]);
    }

    #[Route('/recherche-candidats', name: 'app_dashboard_entreprise_recherche_candidats')]
    public function rechercheCandidats(Request $request, CandidateProfileRepository $candidatRepository, PaginatorInterface $paginatorInterface): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $secteurs = $user->getEntrepriseProfile()->getSecteurs();
        $searchData = new SearchCandidateData();
        $searchData->setSecteurs($secteurs->toArray());
        $form = $this->createForm(SearchCandidateTypeCopy::class, $searchData);
        $form->handleRequest($request);
        $data = $this->em->getRepository(CandidateProfile::class)->findAllValid();
        $favoris = $this->favorisRepository->findBy([
            'entreprise' => $user->getEntrepriseProfile(),
        ]);
        if ($form->isSubmitted() && $form->isValid()) {
            $secteurs = $form->get('secteurs')->getData();
            $titre = $form->get('titre')->getData();
            $competences = $form->get('competences')->getData();
            $langues = $form->get('langue')->getData();
            $page = $form->get('page')->getData();
            $competencesArray = $competences instanceof \Doctrine\Common\Collections\Collection ? $competences->toArray() : $competences;
            $languesArray = $langues instanceof \Doctrine\Common\Collections\Collection ? $langues->toArray() : $competences;
            $titreValues = array_map(function ($titreObject) {
                return $titreObject->getTitre(); // Assurez-vous que getTitre() renvoie la chaîne du titre
            }, $titre);
            $data = $this->entrepriseManager->filter($secteurs, $titreValues, $competencesArray, $languesArray);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/entreprise/candidat/_candidats.html.twig', [
                        'candidats' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            12
                        ),
                        'result' => $data
                    ]),
                ]);
            }
        }else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            
            if (!empty($errors)) {
                // Si la requête est AJAX mais le formulaire n'est pas valide
                return new JsonResponse([
                    'errors' => $errors,
                ], 400);
            }
            
            if ($request->isXmlHttpRequest()) {
                // Si la requête est AJAX mais le formulaire n'est pas valide
                return new JsonResponse([
                    'error' => 'Formulaire invalide ou données incorrectes.',
                ], 400);
            }
        }

        return $this->render('dashboard/entreprise/candidat/index.html.twig', [
            'candidats' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                12
            ),
            'favoris' => $favoris,
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/details-candidat/{id}', name: 'app_dashboard_entreprise_details_candidat')]
    public function detailsCandidat(Request $request, CandidateProfile $candidateProfile): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $favori = false;
        $like = $this->favorisRepository->findOneBy([
            'entreprise' => $entreprise,
            'candidat' => $candidateProfile
        ]);
        if($like){
            $favori = true;
        }

        // Calcul de l'âge
        $now = new \DateTime();
        $age = null;
        if ($candidateProfile->getBirthday() !== null) {
            $age = $now->diff($candidateProfile->getBirthday())->y;
        }
         // Convertir le code ISO en nom de pays
        $countryName = Countries::getName($candidateProfile->getLocalisation());

        return $this->render('dashboard/entreprise/candidat/show.html.twig', [
            'candidat' => $candidateProfile,
            'age' => $age,
            'favori' => $favori,
            'countryName' => $countryName,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidateProfile),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidateProfile),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidateProfile),
            
        ]);
    }

    #[Route('/rendez-vous', name: 'app_dashboard_entreprise_rendez_vous')]
    public function rendezVous(): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/metting/index.html.twig', [
            'mettings' => $entreprise->getMettings(),
            'annonces' => $entreprise->getJobListings(),
            'applications' => $entreprise->getAllApplications(),
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_entreprise_notifications')]
    public function notifications(): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/notification/index.html.twig', [
            'notifications' => $user->getRecus(),
        ]);
    }

    #[Route('/profil', name: 'app_dashboard_entreprise_profil')]
    public function profil(Request $request): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        $form = $this->createForm(EditEntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entreprise);
            $this->em->flush();
            $this->addFlash('success', 'Profil modifié avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_profil');
        }

        return $this->render('dashboard/entreprise/profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/simulateur', name: 'app_dashboard_entreprise_profil')]
    public function simulateur(Request $request): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        $form = $this->createForm(EditEntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entreprise);
            $this->em->flush();
            $this->addFlash('success', 'Profil modifié avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_profil');
        }

        return $this->render('dashboard/entreprise/profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/simulations', name: 'app_dashboard_entreprise_simulations')]
    public function simulations(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/entreprise/simulation/simulations.html.twig', [
            'simulateurs' => $this->simulateurRepository->findSimulateursNotDeletedForEmploye($user->getEmploye()),
        ]);
    }

    #[Route('/simulation/view/{id}', name: 'app_dashboard_entreprise_simulation_view')]
    public function viewSimulateur(Request $request, Simulateur $simulateur): Response
    {
        $results = $this->simulateurEntrepriseManager->simulate($simulateur);
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $employe = $user->getEmploye();
        // $contrat = new Contrat();
        // $contrat->setSimulateur($simulateur);
        // $contrat->setEmploye($employe);
        // $contrat->setSalaireBase($results['salaire_de_base_euro']);
        // $contrat->setStatus(Contrat::STATUS_PENDING);
        // $form = $this->createForm(ContratHiddenType::class, $contrat);
        // $form->handleRequest($request);
        // if($form->isSubmitted() && $form->isValid()){
        //     $contrat = $form->getData();
        //     $this->em->persist($contrat);
        //     $this->em->flush();
        //     /** Envoi mail */
        //     $this->mailManager->newPortage($contrat->getEmploye()->getUser(), $contrat);
        //     $this->addFlash('success', 'Demande d\'information envoyée, vous allez être rappelé dans les prochains jours.');
        // }

        return $this->render('dashboard/entreprise/simulation/view.html.twig', [
            // 'form' => $form->createView(),
            'simulateur' => $simulateur,    
            'results' => $results,
        ]);
    }

    #[Route('/service', name: 'app_dashboard_entreprise_service')]
    public function service(): Response
    {        
        return $this->render('dashboard/entreprise/service/service.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/contact/profil/{id}', name: 'app_dashboard_entreprise_contact_profile')]
    public function contactProfile(Request $request, CandidateProfile $candidateProfile): Response
    {        
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $titre = "Demande de devis profil Olona-talents";
        $titreMod = 'Demande de devis de '.$this->appExtension->generatePseudo($candidateProfile).' pour '.$entreprise->getNom().' Olona-talents';
        $contenu = '        
        <p>Votre demande de devis sur le candidat '.$this->appExtension->generatePseudo($candidateProfile).' a été bien prise en compte. Nous sommes ravis de faciliter cette connexion et espérons qu\'elle sera fructueuse pour vos projets futurs.</p>
        <p>Nous souhaitons vous informer que notre équipe va maintenant procéder à une vérification préliminaire des informations et s\'assurer de la disponibilité du candidat. Nous nous efforçons de maintenir des normes élevées dans la mise en relation de nos clients avec des professionnels qualifiés.</p>
        <p>Vous pouvez vous attendre à recevoir une mise à jour de notre part dans les prochains jours ouvrables. Pendant ce temps, n\'hésitez pas à consulter le profil détaillé du candidat sur notre plateforme pour mieux préparer votre interaction future.</p>
        ';
        $contenuMod = '        
            <p>Nous souhaitons vous informer que l\'entreprise '.$entreprise->getNom().' souhaite contacter le profil '.$this->appExtension->generatePseudo($candidateProfile).'</p>
            <p><a href="'.$this->urlGenerator->generate('app_dashboard_moderateur_candidat_view',['id' => $candidateProfile->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'"> Assigner </a></p>
        ';
        $this->notificationManager->notifyModerateurs($user, Notification::TYPE_CONTACT, $titreMod, $contenuMod );
        $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $user, Notification::TYPE_CONTACT, $titre, $contenu );
        /** Envoi email modérateur */
        $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            "Demande de devis par ".$entreprise->getNom(),
            "moderateur/mail_demande_contact.html.twig",
            [
                'entreprise' => $entreprise,
                'profil' => $candidateProfile,
                'dashboard_url' => $this->urlGenerator->generate(
                    'app_dashboard_moderateur_candidat_view',
                    [
                        'id' => $candidateProfile->getId(),
                    ], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
        /** Envoi email entreprise */
        $this->mailerService->send(
            $user->getEmail(),
            "Demande de devis profil Olona-talents",
            "entreprise/mail_demande_contact.html.twig",
            [
                'user' => $user,
                'profil' => $candidateProfile,
                'dashboard_url' => $this->urlGenerator->generate(
                    'app_dashboard_entreprise_recherche_candidats',
                    [], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
        $this->addFlash('success', 'Demande de devis envoyé');

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_entreprise_details_candidat');
    }

    #[Route('/accept/profil/{id}', name: 'app_dashboard_entreprise_accept_profile')]
    public function acceptProfile(Request $request, Assignation $assignation): Response
    {        
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $titre = 'Demande de contact profil Olona-talents';
        $titreMod = 'Demande de mise en relation de '.$this->appExtension->generatePseudo($assignation->getProfil()).' pour '.$entreprise->getNom();
        $contenu = '        
            <p>Votre demande de mise en relation avec le candidat '.$this->appExtension->generatePseudo($assignation->getProfil()).' a été bien prise en compte. Nous sommes ravis de faciliter cette connexion et espérons qu\'elle sera fructueuse pour vos projets futurs.</p>
            <p>Nous souhaitons vous informer que notre équipe va maintenant procéder à une vérification préliminaire des informations et s\'assurer de la disponibilité du candidat. Nous nous efforçons de maintenir des normes élevées dans la mise en relation de nos clients avec des professionnels qualifiés.</p>
            <p>Vous pouvez vous attendre à recevoir une mise à jour de notre part dans les prochains jours ouvrables. Pendant ce temps, n\'hésitez pas à consulter le profil détaillé du candidat sur notre plateforme pour mieux préparer votre interaction future.</p>
        ';
        $contenuMod = '  
            <p>Nous souhaitons vous informer que l\'entreprise '.$entreprise->getNom().' souhaite avoir une mise en relation avec le profil '.$this->appExtension->generatePseudo($assignation->getProfil()).'</p>
            <p>Voici le détails :</p>
            <p><strong>Titre de l\'annonce</strong> : <br>'.$assignation->getJobListing()->getTitre().'</p>
            <p><strong>Date d\'assignation</strong> : <br>'.$assignation->getDateAssignation()->format('d/m/Y').'</p>
            <p><strong>Candidat</strong> : <br>'.$assignation->getProfil().'</p>
            <p><strong>Entreprise</strong> : <br>'.$entreprise->getNom().'</p>
            <p><a href="'.$this->urlGenerator->generate('app_dashboard_moderateur_assignation_view',['id' => $assignation->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'"> Organiser un RDV </a></p>
        ';
        $this->notificationManager->notifyModerateurs($user, Notification::TYPE_CONTACT, $titreMod, $contenuMod );
        $this->notificationManager->createNotification($this->moderateurManager->getModerateurs()[1], $user, Notification::TYPE_CONTACT, $titre, $contenu );
        /** Envoi email modérateur */
        $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            "Demande de mise en relation par ".$entreprise->getNom(),
            "moderateur/mail_demande_rdv.html.twig",
            [
                'entreprise' => $entreprise,
                'profil' => $assignation->getProfil(),
                'assignation' => $assignation,
                'dashboard_url' => $this->urlGenerator->generate(
                    'app_dashboard_moderateur_assignation_view',
                    [
                        'id' => $assignation->getId(),
                    ], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
        /** Envoi email entreprise */
        $this->mailerService->send(
            $user->getEmail(),
            "Demande de mise en relation Olona-talents",
            "entreprise/mail_demande_rdv.html.twig",
            [
                'user' => $user,
                'profil' => $assignation->getProfil(),
                'dashboard_url' => $this->urlGenerator->generate(
                    'app_dashboard_entreprise_recherche_candidats',
                    [], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
        $this->addFlash('success', 'Demande de mise en relation envoyée');

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_entreprise_details_candidat');
    }
}
