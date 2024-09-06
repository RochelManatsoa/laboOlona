<?php

namespace App\Twig\Components;

use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\BusinessModel\CreditRepository;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('credit_user_component')]
class CreditUserComponent
{
    use DefaultActionTrait;
    
    public function __construct(
        private CreditRepository $creditRepository,
        private Security $security,
    ){}

    public function getCreditUser(): int
    {
        $credit = 0;
        if($this->security->getUser()){
            $credit = $this->creditRepository->findOneBy(['user' => $this->security->getUser()])->getTotal();
        }

        return $credit;
    }
}