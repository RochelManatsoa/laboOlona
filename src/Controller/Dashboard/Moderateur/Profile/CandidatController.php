<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use DateTime;
use App\Twig\AppExtension;
use App\Entity\Candidate\CV;
use App\Entity\Notification;
use App\Entity\TemplateEmail;
use App\Service\FileUploader;
use App\Service\PdfProcessor;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Manager\AssignationManager;
use App\Manager\NotificationManager;
use App\Entity\Entreprise\JobListing;
use App\Form\Moderateur\EditedCvType;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\Assignation;
use App\Form\Candidat\AvailabilityType;
use App\Data\Profile\CandidatSearchData;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Moderateur\AssignationFormType;
use App\Form\Moderateur\Profile\CandidatType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Form\Moderateur\Profile\CandidatCvType;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use App\Form\Moderateur\Profile\CandidatSearchFormType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PdfProcessor $pdfProcessor,
        private CandidateProfileRepository $candidateProfileRepository,
        private PaginatorInterface $paginatorInterface,
        private NotificationManager $notificationManager,
        private AssignationManager $assignationManager,
        private AppExtension $appExtension,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private FileUploader $fileUploader,
        private ProfileManager $profileManager,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_dashboard_moderateur_profile_candidat')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new CandidatSearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(CandidatSearchFormType::class, $data);
        $form->handleRequest($request);
        $candidats = $this->candidateProfileRepository->findSearch($data);

        return $this->render('dashboard/moderateur/profile/candidat/index.html.twig', [
            'candidats' => $candidats,
            'form' => $form->createView(),
            'templateEmails' => $this->em->getRepository(TemplateEmail::class)->findAll(),
        ]);
    }
    
    #[Route('/action-group', name: 'app_dashboard_moderateur_profile_candidat_action_group', methods: ['POST'])]
    public function actionGroup(Request $request): Response
    {
        $selectedProfiles = $request->request->all('selectedProfiles');
        $action = $request->request->get('action');
        $emailTitle = $request->request->get('emailTitle');
        $emailContent = $request->request->get('emailContent');

        if (empty($selectedProfiles)) {
            $this->addFlash('warning', 'Aucun profil sélectionné.');
            return $this->redirectToRoute('app_dashboard_moderateur_profile_candidat');
        }

        switch ($action) {
            case 'valid':
                foreach ($selectedProfiles as $profileId) {
                    $profile = $this->candidateProfileRepository->find($profileId);
                    if ($profile) {
                        $profile->setStatus(CandidateProfile::STATUS_VALID);
                        $this->em->persist($profile);
                    }
                }
                $this->em->flush();
                $this->addFlash('success', 'Les profils sélectionnés ont été validés.');
            break;

            case 'pending':
                foreach ($selectedProfiles as $profileId) {
                    $profile = $this->candidateProfileRepository->find($profileId);
                    if ($profile) {
                        $profile->setStatus(CandidateProfile::STATUS_PENDING);
                        $this->em->persist($profile);
                    }
                }
                $this->em->flush();
                $this->addFlash('success', 'Les profils sélectionnés ont été mis en attente.');
            break;

            case 'relance':
                foreach ($selectedProfiles as $profileId) {
                    $profile = $this->candidateProfileRepository->find($profileId);
                    if ($profile) {
                        // Envoyer l'email de relance
                        $this->mailerService->sendMultipleRelanceEmail($profile, $emailTitle, $emailContent);
                    }
                }
                $this->addFlash('success', 'Les candidats sélectionnés ont été relancés.');
            break;

            default:
                $this->addFlash('warning', 'Aucune action valide sélectionnée.');
                break;
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_profile_candidat');
    }

    #[Route('/{id}', name: 'app_dashboard_moderateur_profile_candidat_view')]
    public function view(Request $request, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $formCandidate = $this->createForm(CandidatType::class, $candidat);
        $formCvCandidate = $this->createForm(CandidatCvType::class, $candidat);
        $formCandidate->handleRequest($request);
        $formCvCandidate->handleRequest($request);
        if($formCandidate->isSubmitted() && $formCandidate->isValid()){
            $this->em->persist($formCandidate->getData());
            $this->em->flush();
            $this->addFlash('success', 'Modification effectuée');
        }
        if($formCvCandidate->isSubmitted() && $formCvCandidate->isValid()){
            $cvFile = $formCvCandidate->get('cv')->getData();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);

                // Process the PDF with Tesseract and store the response
                $pdfPath = $this->fileUploader->getTargetDirectory() . '/' . $fileName[0];
                $this->pdfProcessor->processPdf($pdfPath, $candidat);
                $this->profileManager->saveCV($fileName, $candidat);
            }
            $this->em->persist($formCvCandidate->getData());
            $this->em->flush();
        }
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
        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($candidat->getCandidat());
        $notification->setType(Notification::TYPE_PROFIL);
        $notification->setIsRead(false);
        $notification->setTitre("Information sur votre profil Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour ".$candidat->getCandidat()->getPrenom().",</p>
            <p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p>
            <p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p>
            <p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p>
            <p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>
            <p>".$this->userService->getCurrentUser()->getPrenom()." de l'équipe Olona Talents</p>
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



        return $this->render('dashboard/moderateur/profile/candidat/view.html.twig', [
            'candidat' => $candidat,
            'cvForms' => $cvForms, 
            'formAssignation' => $formAssignation->createView(),
            'form' => $form->createView(),
            'formCvCandidate' => $formCvCandidate->createView(),
            'formCandidate' => $formCandidate->createView(),
            'notifications' => $this->em->getRepository(Notification::class)->findBy([
                'destinataire' => $candidat->getCandidat()
            ], ['id' => 'DESC']),
        ]);
    }
}
