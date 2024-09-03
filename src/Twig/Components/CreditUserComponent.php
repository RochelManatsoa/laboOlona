<?php

namespace App\Twig\Components;

use App\Service\User\UserService;
use App\Repository\BusinessModel\CreditRepository;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('credit_user_component')]
class CreditUserComponent
{
    use DefaultActionTrait;
    
    public function __construct(
        private CreditRepository $creditRepository,
        private UserService $userService,
    ){}

    public function getCreditUser(): int
    {
        return $this->creditRepository->findOneBy(['user' => $this->userService->getCurrentUser()])->getTotal();
    }
}