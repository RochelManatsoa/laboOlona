<?php

namespace App\Controller\Moderateur;

use App\Entity\Notification;
use App\Entity\User;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Manager\NotificationManager;
use App\Repository\NotificationRepository;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/dashboard/moderateur/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private NotificationRepository $notificationRepository,
        private NotificationManager $notificationManager,
    ) {
    }
    
    #[Route('/', name: 'app_dashboard_moderateur_notification')]
    public function index(Request $request): Response
    {
        $isRead = $request->query->get('isRead');
        $page = $request->query->get('page', 1);
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('moderateur/notification/index.html.twig', [
            'notifications' => $this->notificationRepository->findByDestinataire(
                $user, 
                $isRead,
                ['id' => 'DESC'], 
                Notification::STATUS_DELETED,
                $page
            ),
        ]);
    }
    
    #[Route('/{id}', name: 'app_dashboard_moderateur_notification_show')]
    public function view(Notification $notification): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $notification->setIsRead(true);
        $this->notificationManager->save($notification);

        return $this->render('dashboard/notification/view.html.twig', [
            'notification' => $notification,
        ]);
    }
    
    #[Route('/delete/{id}', name: 'app_dashboard_moderateur_notification_delete')]
    public function delete(Notification $notification): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $notification->setStatus(Notification::STATUS_DELETED);
        $this->notificationManager->save($notification);

        return $this->redirectToRoute('app_dashboard_moderateur_notification', []);
    }
    
    #[Route('/see/all', name: 'app_dashboard_moderateur_notification_see_all')]
    public function seeAll(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $notifications = $this->notificationRepository->findByDestinataireAndStatusNot(
            $user, 
            ['id' => 'DESC'], 
            Notification::STATUS_DELETED,
            0
        );
        
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
            $this->notificationManager->save($notification);
        }

        return $this->redirectToRoute('app_dashboard_moderateur_notification', []);
    }
}
