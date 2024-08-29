<?php

namespace App\Controller\V2\Candidate;

use App\Data\V2\JobOfferData;
use App\Manager\ProfileManager;
use App\Entity\Vues\AnnonceVues;
use App\Entity\Referrer\Referral;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\ModerateurManager;
use App\Manager\ApplicationManager;
use App\Entity\BusinessModel\Credit;
use App\Manager\OlonaTalentsManager;
use App\Entity\Entreprise\JobListing;
use App\Service\ElasticsearchService;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Form\Candidat\ApplicationsType;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use App\Entity\CandidateProfile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/job-offer')]
class JobOfferController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ElasticsearchService $elasticsearch,
        private OlonaTalentsManager $olonaTalentsManager,
        private ApplicationManager $applicationManager,
        private CreditManager $creditManager,
        private ProfileManager $profileManager,
        private ApplicationsRepository $applicationsRepository,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private MailerService $mailerService,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_job_offer')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        $data = new JobOfferData();
        $data->page = $request->get('page', 1);
        $data->candidat = $candidat;
        $params = [];

        $query = $request->query->get('q', $candidat->getSecteurs()[0]->getNom());
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['searchQuery'] = $query;
        $paramsJoblisting = $this->olonaTalentsManager->getParamsJoblisting($from, $size, $query);
        
        $joblistings = $this->elasticsearch->search($paramsJoblisting);
        $totalJobListingsResults = $joblistings['hits']['total']['value'];
        $totalAnnoncesPages = ceil($totalJobListingsResults / $size);
        $params['totalAnnoncesPages'] = $totalAnnoncesPages;
        $params['annonces'] = $joblistings['hits']['hits'];
        $params['totalJobListingsResults'] = $totalJobListingsResults;

        return $this->render('v2/dashboard/candidate/job_offer/index.html.twig', $params);
    }
    
    #[Route('/view-details/{id}', name: 'app_v2_view_job_offer')]
    public function details(Request $request, int $id): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_candidate_job_offer');
        }

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $annonce->getEntreprise()->getEntreprise(),
        ]);

        return $this->render('v2/dashboard/candidate/job_offer/details.html.twig', [
            'annonce' => $annonce,
            'purchasedContact' => $purchasedContact,
        ]);
    }
    
    #[Route('/view/{id}', name: 'app_v2_candidate_view_job_offer')]
    public function viewJobOffer(Request $request, int $id): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $candidat = $this->userService->checkProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_view_job_offer', ['id' => $id]);
        }
        $recruiter = $annonce->getEntreprise();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_candidate_job_offer');
        }
        $application = $this->applicationsRepository->findOneBy([
            'candidat' => $candidat,
            'annonce' => $annonce
        ]);

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $recruiter->getEntreprise(),
        ]);

        $applied = false;

        if(!$application instanceof Applications){
            $applied = true;
            $application = $this->applicationManager->init();
            $application->setAnnonce($annonce);
            $application->setCvLink($candidat->getCv());
            $application->setCandidat($candidat);
        }
        $form = $this->createForm(ApplicationsType::class, $application);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $application = $form->getData();
            $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $currentUser->getEmail()]);
            if($refered instanceof Referral){
                $refered->setStep(4);
                $this->em->persist($refered);
            }
            $message = 'Contact du candidat affiché';
            $success = true;
            $status = 'Succès';
        
            $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_JOB);
            $response = $this->creditManager->adjustCredits($this->userService->getCurrentUser(), $creditAmount);
            
            if (isset($response['error'])) {
                $message = $response['error'];
                $success = false;
                $status = 'Echec';
            }

            if (isset($response['success'])) {
                $this->applicationManager->saveForm($form);
            }
    
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
                $currentUser->getEmail(),
                "Votre candidature a été prise en compte sur Olona Talents",
                "candidat/notification_candidature.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'candidature' => $application,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_v2_candidate_application', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
    
            /** Envoi email entreprise */
            $this->mailerService->send(
                $recruiter->getEntreprise()->getEmail(),
                "Nouvelle candidature reçue sur votre annonce Olona-talents.com",
                "entreprise/notification_candidature.html.twig",
                [
                    'user' => $recruiter->getEntreprise(),
                    'candidature' => $application,
                    'candidat' => $candidat,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_annonce_view_default', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/candidate/live.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'credit' => $currentUser->getCredit()->getTotal(),
                ]);
            }


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
        
        return $this->render('v2/dashboard/candidate/job_offer/view.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
            'applied' => $applied,
            'purchasedContact' => $purchasedContact,
            'form' => $form,
        ]);
    }
}
