<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Service\User\UserService;

class FinanceVoter extends Voter
{

    public function __construct(
        private UserService $userService
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        // ici on définit dans quels cas ce Voter est utilisé
        return $attribute === 'FINANCE_ACCESS';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // si l'utilisateur n'est pas connecté, on refuse l'accès
        if (!$user instanceof User) {
            return false;
        }

        return in_array("ROLE_FINANCE", $user->getRoles());
    }
}
