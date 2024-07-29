<?php

namespace App\Manager;

use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OlonaTalentsManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack
    ){}

    public function getParams(): array
    {
        $params = [];
        $params['top_candidats'] = $this->em->getRepository(CandidateProfile::class)->findTopRanked();
        $params['top_annonces'] = $this->em->getRepository(JobListing::class)->findFeaturedJobListing();
        $params['top_entreprises'] = $this->em->getRepository(EntrepriseProfile::class)->findTopRanked();

        return $params;
    }
}