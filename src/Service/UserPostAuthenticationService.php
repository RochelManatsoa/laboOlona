<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPostAuthenticationService
{
    public function __construct(
        private EntityManagerInterface $em,
    ){}

    public function updateLastLoginDate(UserInterface $user):void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setLastLogin(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
    }
}
