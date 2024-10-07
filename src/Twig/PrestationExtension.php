<?php

namespace App\Twig;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use App\Entity\User;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PrestationExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private AppExtension $appExtension,
    ){}
    
    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAuthor', [$this, 'getAuthor']),
            new TwigFunction('getTotalCount', [$this, 'getTotalCount']),
        ];
    }

    public function getAuthor(Prestation $prestation): string
    {
        if($prestation->getCandidateProfile() instanceof CandidateProfile){
            $profile = $prestation->getCandidateProfile();
            return $this->appExtension->generatePseudo($profile);
        }
        if($prestation->getEntrepriseProfile() instanceof EntrepriseProfile){
            $profile = $prestation->getEntrepriseProfile();
            return $this->appExtension->generateReference($profile);
        }

        return '';
    }

    public function getTotalCount(User $user): int
    {
        if($user->getCandidateProfile() instanceof CandidateProfile){
            $profile = $user->getCandidateProfile();
        }
        if($user->getEntrepriseProfile() instanceof EntrepriseProfile){
            $profile = $user->getEntrepriseProfile();
        }
        
        return count($profile->getPrestations());
    }

}