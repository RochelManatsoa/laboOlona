<?php

namespace App\Manager\Referrer;

use App\Entity\ReferrerProfile;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;

class ReferenceManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService
    ){}
    
    public function getReferenceAnnonce(array $data): array
    {
        $references = [];
        foreach ($data as $referral) {
            $annonce = $referral->getAnnonce();
            $annonceId = $annonce->getId();
        
            if (!array_key_exists($annonceId, $references)) {
                $references[$annonceId] = [
                    'annonce' => $annonce,
                    'referrals' => [],
                ];
            }
        
            $references[$annonceId]['referrals'][] = $referral;
        }

        return $references;

    }
    
    public function getCooptationStat(array $data): array
    {
        $stats = [];
        foreach ($data as $referral) {
            $annonce = $referral->getAnnonce();
            $annonceId = $annonce->getId();
        
            if (!array_key_exists($annonceId, $stats)) {
                $stats[$annonceId] = [
                    'annonce' => $annonce,
                    'referrals' => [],
                ];
            }
        
            $stats[$annonceId]['referrals'][] = $referral;
        }

        return $stats;

    }
}