<?php

namespace App\Twig;

use App\Entity\Candidate\TarifCandidat;
use App\Entity\User;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\ReferrerProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\JobListing;
use Twig\Extension\AbstractExtension;
use App\Entity\Moderateur\Assignation;
use App\Entity\Entreprise\BudgetAnnonce;
use App\Entity\Finance\Simulateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StatusExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private ReferrerProfileRepository $referrerProfileRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('reffererStatusLabel', [$this, 'reffererStatusLabel']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('arrayInverseDevise', [$this, 'arrayInverseDevise']),
            new TwigFunction('arrayInverseCandidatTarifType', [$this, 'arrayInverseCandidatTarifType']),
            new TwigFunction('arrayInverseTarifType', [$this, 'arrayInverseTarifType']),
            new TwigFunction('satusSimulateur', [$this, 'satusSimulateur']),
        ];
    }

    public function arrayInverseDevise(): array
    {
        return BudgetAnnonce::arrayInverseDevise();
    }

    public function arrayInverseTarifType(): array
    {
        return BudgetAnnonce::arrayInverseTarifType();
    }

    public function arrayInverseCandidatTarifType(string $typeTarif)
    {
        $type = TarifCandidat::arrayInverseTarifType()[$typeTarif] ?? '';
        
        return $type;
    }

    public function satusSimulateur(Simulateur $simulateur)
    {
        $type = $simulateur->getStatus() ?? '';
        switch ($type) {
            case Simulateur::STATUS_VALID :
                $status = '<span class="badge text-bg-success">Valide</span>';
                break;

            case Simulateur::STATUS_DELETED :
                $status = '<span class="badge text-bg-dark">Effacé</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-success">Valide</span>';
                break;
        }
        
        return $status;
    }

}