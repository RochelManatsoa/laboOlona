<?php

namespace App\Twig\Components;

use App\Entity\CandidateProfile;
use App\Entity\User;
use App\Repository\CandidateProfileRepository;
use App\Repository\UserRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('candidat_component')]
class CandidatComponent
{
    public string $type = 'success';
    public int $id;

    public function __construct(
        private CandidateProfileRepository $candidateProfileRepository,
        private UserRepository $userRepository,
    ){
    }

    public function getCandidat(): CandidateProfile
    {
        return $this->candidateProfileRepository->find($this->id);
    }

    public function getUserProfile(): User
    {
        return $this->userRepository->find($this->getCandidat()->getCandidat()->getId());
    }
}