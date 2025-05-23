<?php

namespace App\Security;

use App\WhiteLabel\Entity\Client1\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class Client1UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private ManagerRegistry $registry)
    {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->registry->getManager('client1')->getRepository(User::class)
            ->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException("User not found");
        }
        dd($user); 

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    #[\ReturnTypeWillChange]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $em = $this->registry->getManager('client1');
        $user->setPassword($newHashedPassword);
        $em->persist($user);
        $em->flush();
    }
}

