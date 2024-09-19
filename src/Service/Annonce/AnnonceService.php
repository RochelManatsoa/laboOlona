<?php

namespace App\Service\Annonce;

use App\Entity\Entreprise\JobListing;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\IdentityRepository;
use App\Repository\UserRepository;
use App\Service\User\UserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AnnonceService
{
    public function __construct(
        private Security $security,
        private UserService $userService,
        private UserRepository $userRepository,
        private JobListingRepository $jobListingRepository,
        private RequestStack $requestStack,
    ){
    }

    public function add(int $id): void
    {
        $annonce = $this->requestStack->getSession()->get('annonce', []);
        $annonce[$id] =  1;

        $this->requestStack->getSession()->set('annonce', $annonce);
    }

    public function remove(int $id): void
    {
        $annonce = $this->requestStack->getSession()->get('annonce', []);
        if(!empty($annonce[$id])){
            unset($annonce[$id]);
        }

        $this->requestStack->getSession()->set('annonce', $annonce);
    }

    public function getannonceSession():array
    {
        $annonces = [];
        $annonceSession = $this->requestStack->getSession()->get('annonce', []);
        foreach ($annonceSession as $key => $value) {
           $annonces[] =  $this->jobListingRepository->find($key);
        }

        return $annonces;
    }

    public function storePreviousURL()
    {
        $previousRequest = $this->requestStack->getParentRequest();

        if ($previousRequest) {
            $this->requestStack->getSession()->set('original_uri_before_registration', $previousRequest->getUri());
        }
    }
}