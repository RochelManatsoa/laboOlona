<?php

namespace App\Twig\Components;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\User\UserService;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('count_notification_component')]
class CountNotificationComponent
{
    use DefaultActionTrait;
    
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