<?php

namespace App\Security\Voter;

use App\Entity\Finance\Simulateur;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SimulationVoter extends Voter
{
    public const EDIT = 'SIMULATION_EDIT';
    public const VIEW = 'SIMULATION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Simulateur;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if(!$subject instanceof Simulateur){
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
                return $subject->getEmploye()->getUser()->getId() === $user->getId();
                break;

            case self::VIEW:
                return $subject->getEmploye()->getUser()->getId() === $user->getId();
                break;
        }

        return false;
    }
}
