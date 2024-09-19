<?php

namespace App\Twig;

use App\Entity\User;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Boost;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\BusinessModel\BoostVisibility;
use App\Manager\BusinessModel\BoostVisibilityManager;

class BoostExtension extends AbstractExtension
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private BoostVisibilityManager $boostVisibilityManager,
    ){}
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('boostStatus', [$this, 'boostStatus']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('checkBoost', [$this, 'checkBoost']),
            new TwigFunction('getBoostInfo', [$this, 'getBoostInfo']),
        ];
    }

    public function checkBoost(User $user): string
    {
        $userBoost = '';
        if($user->getCandidateProfile() instanceof CandidateProfile){
            $userBoost = '<button class="btn btn-sm btn-danger text-uppercase fw-bold px-4" data-bs-toggle="modal" data-bs-target="#boostProfile" data-bs-whatever="@mdo"><i class="bi bi-rocket-takeoff me-2"></i> Booster mon profil</button>';
            $candidat = $user->getCandidateProfile();
            if($candidat->getBoostVisibility() instanceof BoostVisibility){
                $boostVisibility = $candidat->getBoostVisibility();
                if(!$this->boostVisibilityManager->isExpired($boostVisibility)){
                    $userBoost = '<span class="text-center"><span class="fs-5 fw-bold text-uppercase"><i class="bi bi-rocket me-2"></i> Profil boosté</span><br><span class="small fw-light"> Jusqu\'au '.$boostVisibility->getEndDate()->format('d-m-Y \à H:i').' </span></span>';
                }
            }
        }
        if($user->getEntrepriseProfile() instanceof EntrepriseProfile){
            $userBoost = '<button class="btn btn-sm btn-danger text-uppercase fw-bold" data-bs-toggle="modal" data-bs-target="#boostProfile" data-bs-whatever="@mdo"><i class="bi bi-rocket-takeoff me-2"></i> Booster mon entreprise</button>';
            $recruiter = $user->getEntrepriseProfile();
            if($recruiter->getBoostVisibility() instanceof BoostVisibility){
                $boostVisibility = $recruiter->getBoostVisibility();
                if(!$this->boostVisibilityManager->isExpired($boostVisibility)){
                    $userBoost = '<span class="text-center"><span class="fs-5 fw-bold text-uppercase"><i class="bi bi-rocket me-2"></i> Entreprise boosté</span><br><span class="small fw-light"> Jusqu\'au '.$boostVisibility->getEndDate()->format('d-m-Y \à H:i').' </span></span>';
                }
            }
        }

        return $userBoost;
    }

    public function getBoostInfo(string $boostStrId): ?Boost
    {
        if (preg_match('/\d+$/', $boostStrId, $matches)) {
            $boostId = $matches[0];
        }

        return $this->em->getRepository(Boost::class)->find($boostId);
    }

}