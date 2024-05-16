<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use DateTime;
use App\Entity\Notification;
use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Manager\NotificationManager;
use App\Service\Mailer\MailerService;
use App\Form\Candidat\AvailabilityType;
use App\Data\Profile\CandidatSearchData;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Moderateur\Profile\CandidatType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use App\Form\Moderateur\Profile\CandidatSearchFormType;
use App\Manager\CandidatManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private PaginatorInterface $paginatorInterface,
        private NotificationManager $notificationManager,
        private CandidatManager $candidatManager,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
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
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_moderateur_profile_candidat_view')]
    public function view(Request $request, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $formCandidate = $this->createForm(CandidatType::class, $candidat);
        $formCandidate->handleRequest($request);
        $notification = $this->notificationManager->createNotification(
            $this->userService->getCurrentUser(),
            $candidat->getCandidat(),
            Notification::TYPE_PROFIL,
            "Information sur votre profil Olona Talents",
            "<p>Bonjour [Nom de l'Utilisateur],</p><p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p><p>Pour assurer l'efficacité et la qualité de nos services, il est important que chaque profil soit complet et à jour. Voici les informations manquantes :</p><ol><li>[Information manquante 1]</li><li>[Information manquante 2]</li><li>[Autres informations manquantes, si nécessaire]</li></ol><p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p><p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p><p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>"
        );

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
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_candidat_view');
        }

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
            'form' => $form->createView(),
            'formCandidate' => $formCandidate->createView(),
            'formDispo' => $formDispo->createView(),
            'notifications' => $this->em->getRepository(Notification::class)->findBy([
                'destinataire' => $candidat->getCandidat()
            ], ['id' => 'DESC']),
        ]);
    }
}
