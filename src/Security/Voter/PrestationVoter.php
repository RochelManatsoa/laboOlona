<?php

namespace App\Security\Voter;

use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PrestationVoter extends Voter
{
    public const EDIT = 'PRESTATION_EDIT';
    public const VIEW = 'PRESTATION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Prestation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if(!$subject instanceof Prestation){
            return false;
        }

        if($subject->getEntrepriseProfile() instanceof EntrepriseProfile){
            $profile = $subject->getEntrepriseProfile()->getEntreprise();
        }else{
            $profile = $subject->getCandidateProfile()->getCandidat();
        }

        switch ($attribute) {
            case self::EDIT:
                return $profile->getId() === $user->getId();
                break;

            case self::VIEW:
                return true;
                break;
        }

        return false;
    }
}
