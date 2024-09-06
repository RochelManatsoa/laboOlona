<?php

namespace App\Twig\Components;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('count_notification_component')]
class CountNotificationComponent
{
    use DefaultActionTrait;
    
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ){
    }

    public function getAllNotification(): int
    {
        $count = 0;
        if($this->security->getUser()){
            $count = count($this->notificationRepository->findByDestinataireAndStatusNot(
                $this->security->getUser(), 
                ['id' => 'DESC'], 
                Notification::STATUS_DELETED,
                0
            ));
        }

        return $count;
    }
}