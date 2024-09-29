<?php

namespace App\Twig\Components;

use App\Entity\Entreprise\JobListing;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('count_joblisting_views_component')]
class CountJobLisitingViewsComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?JobListing $jobListing = null;

    public function getViews(): int
    {
        return $this->jobListing ? count($this->jobListing->getAnnonceVues()) : 0;
    }
}