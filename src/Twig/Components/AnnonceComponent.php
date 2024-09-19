<?php

namespace App\Twig\Components;

use App\Entity\Entreprise\JobListing;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('annonce_component')]
class AnnonceComponent
{
    public string $type = 'success';
    public int $id;

    public function __construct(
        private JobListingRepository $jobListingRepository
    ){
    }

    public function getAnnonce(): JobListing
    {
        return $this->jobListingRepository->find($this->id);
    }
}