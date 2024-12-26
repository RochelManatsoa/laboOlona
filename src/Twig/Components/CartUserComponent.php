<?php

namespace App\Twig\Components;

use App\Service\Cart;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('cart_user_component')]
class CartUserComponent
{
    use DefaultActionTrait;
    
    public function __construct(
        private Security $security,
        private Cart $cartService
    ){}

    public function getCreditUser(): int
    {
        $credit = 0;
        if($this->security->getUser()){
            $credit = count($this->cartService->getCart());
        }

        return $credit;
    }
}