<?php

namespace App\WhiteLabel\Twig\Client1;

use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\Favoris;

class WhiteLabelExtension extends AbstractExtension
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isLikedByBOARecruiter', [$this, 'isLikedByBOARecruiter']),
        ];
    }

    public function isLikedByBOARecruiter(EntrepriseProfile $recruiter, int $id):bool
    {
        $candidat = $this->entityManager->getRepository(CandidateProfile::class)->find($id);
        if($candidat){
            $liked = $this->entityManager->getRepository(Favoris::class)->findOneBy([
                'entreprise' => $recruiter,
                'candidat' => $candidat
            ]);
            if($liked){
                return true;
            }
            return false;
        }

        return false;
    }
}