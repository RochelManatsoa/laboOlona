<?php

namespace App\Security;

use App\WhiteLabel\Entity\Client1\User as Client1User;
use App\Entity\User as MainUser;
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
        $emClient1 = $this->registry->getManager('client1');
        $user = $emClient1->getRepository(Client1User::class)->findOneBy(['email' => $identifier]);

        if (!$user) {
            $emDefault = $this->registry->getManager();
            $user = $emDefault->getRepository(MainUser::class)->findOneBy(['email' => $identifier]);
            if (!$user) {
                throw new UserNotFoundException(sprintf('User "%s" not found', $identifier));
            }
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return in_array($class, [Client1User::class, MainUser::class], true) || is_subclass_of($class, Client1User::class) || is_subclass_of($class, MainUser::class);
    }

    #[\ReturnTypeWillChange]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $em = $user instanceof Client1User ? $this->registry->getManager('client1') : $this->registry->getManager();
        $user->setPassword($newHashedPassword);
        $em->persist($user);
        $em->flush();
    }
}

