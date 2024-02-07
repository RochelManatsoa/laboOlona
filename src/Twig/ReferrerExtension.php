<?php

namespace App\Twig;

use App\Entity\Entreprise\JobListing;
use App\Entity\ReferrerProfile;
use App\Repository\ReferrerProfileRepository;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReferrerExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private ReferrerProfileRepository $referrerProfileRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_label', [$this, 'statusLabel']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('stepCooptation', [$this, 'stepCooptation']),
            new TwigFunction('generateCoopteurPseudo', [$this, 'generateCoopteurPseudo']),
            new TwigFunction('getReferrerById', [$this, 'getReferrerById']),
            new TwigFunction('getPrimeByAnnonce', [$this, 'getPrimeByAnnonce']),
        ];
    }

    public function stepCooptation(int $step): string 
    {
        $style = '<span class="badge text-bg-danger">Création compte</span>';
        switch ($step) {
            case '2':
                $style = '<span class="badge text-bg-primary">Validation compte</span>';
                break;
            
            case '3':
                $style = '<span class="badge text-bg-dark">Candidature envoyée</span>';
                break;

            case '4':
                $style = '<span class="badge text-bg-info">Mise en relation</span>';
                break;

            case '5':
                $style = '<span class="badge text-bg-success">Rendez-vous</span>';
                break;

            case '6':
                $style = '<span class="badge text-bg-warning">Accépté</span>';
                break;

            default:
                $style = '<span class="badge text-bg-danger">Création compte</span>';
                break;
        }
        return $style;
    }

    public function generateCoopteurPseudo(ReferrerProfile $referrerProfile)
    {
        $letters = 'CO';
        $paddedId = sprintf('%04d', $referrerProfile->getId());

        return $letters . $paddedId;
    }

    public function getReferrerById(int $id): ReferrerProfile
    {
        return $this->referrerProfileRepository->find($id);
    }

    public function getPrimeByAnnonce(JobListing $jobListing): float
    {
        $rewards = 0;
        $prime = $rewards = $jobListing->getPrime();
        if(null === $prime){
            $rewards = $jobListing->getSalaire() * 0.1 / $jobListing->getNombrePoste();
        }

        return $rewards;
    }

}