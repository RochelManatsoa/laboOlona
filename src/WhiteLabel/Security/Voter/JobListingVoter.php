<?php

namespace App\WhiteLabel\Security\Voter;

use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Entity\Client1\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JobListingVoter extends Voter
{
    public const EDIT = 'JOB_LISTING_EDIT';
    public const VIEW = 'JOB_LISTING_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof JobListing;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof JobListing) {
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    return true;
                }
                return $subject->getEntreprise()->getId() === $user->getEntrepriseProfile()->getId();
            case self::VIEW:
                return true;
        }

        return false;
    }
}
