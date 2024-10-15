<?php

namespace App\Controller\V2;

use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
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
        private UrlGeneratorInterface $urlGeneratorInterface,
        private MailerService $mailerService,
    ){}

    #[Route('/job-offers', name: 'app_v2_job_offer')]
    public function index(Request $request): Response
    {
        $profile = $this->userService->checkProfile();
        $secteurs = $profile->getSecteurs();
        $page = $request->query->get('page', 1);
        $limit = 10;
        $qb = $this->em->getRepository(JobListing::class)->createQueryBuilder('j');
        $qb->where('j.status = :status')
            ->setParameter('status', JobListing::STATUS_PUBLISHED)
            ->andWhere('j.secteur IN (:secteurs)')
            ->setParameter('secteurs', $secteurs)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $joblistings = $qb->getQuery()->getResult();
        
        return $this->render('v2/dashboard/job_offer/index.html.twig', [
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
            'profile' => $profile,
            'joblistings' => $joblistings,
            'nextPage' => $page + 1,
            'hasMore' => count($joblistings) == $limit
        ]);
    }

    #[Route('/api/job-offers', name: 'api_job_offers')]
    public function apiJobOffers(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $profile = $this->userService->checkProfile(); 
        $secteurs = $profile->getSecteurs();
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $qb = $this->em->getRepository(JobListing::class)->createQueryBuilder('j');
        $qb->where('j.status = :status')
            ->setParameter('status', 'published')
            ->andWhere('j.secteur IN (:secteurs)')
            ->setParameter('secteurs', $secteurs)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $joblistings = $qb->getQuery()->getResult();

        return $this->render('v2/dashboard/result/parts/_part_joblistings_list.html.twig', [
            'joblistings' => $joblistings,
        ]);
    }

    #[Route('/job-offer/view/{id}', name: 'app_v2_job_offer_view')]
    public function viewJobOffer(Request $request, int $id): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $candidat = $this->userService->checkProfile();
        if($candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_candidate_view_job_offer', ['id' => $id]);
        }
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_job_offer');
        }

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $annonce->getEntreprise()->getEntreprise(),
        ]);

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

        return $this->render('v2/dashboard/job_offer/view.html.twig', [
            'annonce' => $annonce,
            'purchasedContact' => $purchasedContact,
        ]);
    }

    #[Route('/job-offer/candidate/view/{id}', name: 'app_v2_candidate_view_job_offer')]
    public function candidateViewJobOffer(Request $request, int $id): Response
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $candidat = $this->userService->checkProfile();
        if(!$candidat instanceof CandidateProfile){
            return $this->redirectToRoute('app_v2_job_offer_view', ['id' => $id]);
        }
        $recruiter = $annonce->getEntreprise();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_job_offer');
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
            $message = 'Candidature envoyée';
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
                    'dashboard_url' => $this->urlGeneratorInterface->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
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
                    'dashboard_url' => $this->urlGeneratorInterface->generate('app_v2_applications', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
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
                    'dashboard_url' => $this->urlGeneratorInterface->generate('app_dashboard_moderateur_candidature_annonce_view_default', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
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

            return $this->redirectToRoute('app_v2_applications');
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
        
        return $this->render('v2/dashboard/job_offer/view.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
            'applied' => $applied,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_joblistings'),
            'purchasedContact' => $purchasedContact,
            'form' => $form,
        ]);
    }
}
