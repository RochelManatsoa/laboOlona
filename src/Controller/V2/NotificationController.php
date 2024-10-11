<?php

namespace App\Controller\V2;

use App\Entity\BusinessModel\PurchasedContact;
use App\Entity\User;
use App\Entity\Notification;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Manager\NotificationManager;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
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
        private CreditManager $creditManager,
    ) {
    }
    
    #[Route('/notifications', name: 'app_v2_dashboard_notification')]
    public function index(Request $request): Response
    {
        $isRead = $request->query->get('isRead');
        $page = $request->query->get('page', 1);
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('v2/dashboard/notification/index.html.twig', [
            'notifications' => $this->notificationRepository->findByDestAndStatusNot(
                $user, 
                ['id' => 'DESC'], 
                Notification::STATUS_DELETED,
                $isRead,
                $page,
            ),
        ]);
    }
    
    #[Route('/notification/view/{id}', name: 'app_v2_dashboard_notification_view')]
    public function view(int $id): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $notification = $this->em->getRepository(Notification::class)->find($id);
        if(!$notification instanceof Notification){
            return $this->json([
                'success' => false,
            ], Response::HTTP_BAD_REQUEST);
        }
        $notification->setIsRead(true);
        $this->notificationManager->save($notification);

        return $this->json([
            'success' => true,
            'id' => $notification->getId(),
        ], Response::HTTP_OK);
    }
    
    #[Route('/notification/delete/{id}', name: 'app_v2_dashboard_notification_delete')]
    public function delete(Notification $notification): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $notification->setStatus(Notification::STATUS_DELETED);
        $this->notificationManager->save($notification);

        return $this->redirectToRoute('app_v2_dashboard_notification', []);
    }
    
    #[Route('/notification/see/all', name: 'app_v2_dashboard_notification_see_all')]
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

        return $this->redirectToRoute('app_v2_dashboard_notification', []);
    }
    
    #[Route('/notification/accept/{id}', name: 'app_v2_dashboard_notification_accept')]
    public function accept(int $id): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $purchasedContact = $this->em->getRepository(PurchasedContact::class)->find($id);
        $purchasedContact->setIsAccepted(true);
        $purchasedContact->setAcceptedAt(new \DateTime());
        $this->em->persist($purchasedContact);
        $this->em->flush();
        $urlContacts = $this->urlGenerator->generate(
            'app_v2_contacts',
            [], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->notificationManager->createNotification(
            $purchasedContact->getContact(),
            $purchasedContact->getBuyer(),
            Notification::TYPE_CONTACT,
            'Demande de contact acceptée',
            'Nous avons de bonnes nouvelles ! '.ucfirst(substr($purchasedContact->getContact()->getNom(), 0, 1)).'. '.$purchasedContact->getContact()->getPrenom(). ' a accepté de partager ses coordonnées avec vous. Vous pouvez désormais le contacter directement pour discuter de votre opportunité de collaboration.
            <br>
            <a class="btn btn-primary rounded-pill my-3 px-4" href="'.$urlContacts.'">Mon réseau professionnel</a>
            '
        );

        return $this->redirectToRoute('app_v2_dashboard_notification', []);
    }
    
    #[Route('/notification/refuse/{id}', name: 'app_v2_dashboard_notification_refuse')]
    public function refuse(int $id): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $purchasedContact = $this->em->getRepository(PurchasedContact::class)->find($id);
        $purchasedContact->setIsAccepted(false);
        $this->em->persist($purchasedContact);
        $this->em->flush();
        // $this->creditManager->restablishCredits($purchasedContact->getBuyer(), $purchasedContact->getPrice());
        $urlContacts = $this->urlGenerator->generate(
            'app_v2_contacts',
            [], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->notificationManager->createNotification(
            $purchasedContact->getContact(),
            $purchasedContact->getBuyer(),
            Notification::TYPE_CONTACT,
            'Demande de contact refusée',
            'Nous vous informons que l\'utilisateur a choisi de ne pas partager ses coordonnées pour le moment. En conséquence, les 50 crédits déduits pour cette demande vous ont été restitués. <br>Nous respectons sa décision de confidentialité et vous encourageons à explorer d\'autres profils sur notre plateforme qui pourraient correspondre à vos besoins.
            <br>
            <a class="btn btn-primary rounded-pill my-3 px-4" href="'.$urlContacts.'">Mon réseau professionnel</a>
            '
        );

        return $this->redirectToRoute('app_v2_dashboard_notification', []);
    }
}
