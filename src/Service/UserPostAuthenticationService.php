<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPostAuthenticationService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function updateLastLoginDate(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setLastLogin(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
