<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\BusinessModel\Order;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OrderVoter extends Voter
{
    public const EDIT = 'ORDER_EDIT';
    public const VIEW = 'ORDER_VIEW';
    public const CREATE = 'ORDER_CREATE';
    public const LIST = 'ORDER_LIST';
    public const LIST_ALL = 'ORDER_ALL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            in_array($attribute, [self::CREATE, self::LIST, self::LIST_ALL]) ||
            (
                in_array($attribute, [self::EDIT, self::VIEW])
                && $subject instanceof Order
            );
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                return $subject->getCustomer()->getId() === $user->getId();
                break;
                
            case self::LIST:
            case self::CREATE:
            case self::VIEW:
                return true;
                break;
        }

        return false;
    }
}
