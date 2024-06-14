<?php

namespace App\Twig\Components;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\User\UserService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('count_notification_component')]
class CountNotificationComponent
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private UserService $userService,
    ){
    }

    public function getAllNotification(): int
    {
        return count($this->notificationRepository->findByDestinataireAndStatusNot(
            $this->userService->getCurrentUser(), 
            ['id' => 'DESC'], 
            Notification::STATUS_DELETED,
            0
        ));
    }
}