<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Service\User\UserService;
use App\Repository\CandidateProfileRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('candidat_carousel_top')]
class CandidatCarouselTopComponent
{

    public function __construct(
        private CandidateProfileRepository $candidateProfileRepository,
        private UserService $userService,
    ){
    }

    public function getCandidats(): array
    {
        return $this->candidateProfileRepository->findTopExperts();
    }

    public function getTopRanked(): array
    {
        return $this->candidateProfileRepository->findTopRanked();
    }

    public function getIdentity(): User
    {
        return $this->userService->getCurrentUser();
    }
}