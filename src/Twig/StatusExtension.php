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
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Simulateur;
use App\Entity\Moderateur\Metting;
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
            new TwigFunction('satusEntreprise', [$this, 'satusEntreprise']),
            new TwigFunction('satusMetting', [$this, 'satusMetting']),
            new TwigFunction('typeSimulateur', [$this, 'typeSimulateur']),
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
        $type = $simulateur->getStatusFinance() ?? '';
        switch ($type) {
            case Simulateur::STATUS_LIBRE :
                $status = '<span class="badge text-bg-success">Simulation libre</span>';
                break;

            case Simulateur::STATUS_SEND :
                $status = '<span class="badge text-bg-warning">Demande envoyée</span>';
                break;

            case Simulateur::STATUS_CONTACT :
                $status = '<span class="badge text-bg-info">Prise de contact</span>';
                break;

            case Simulateur::STATUS_RELANCE :
                $status = '<span class="badge text-bg-primary">Relance</span>';
                break;

            case Simulateur::STATUS_CLIENT :
                $status = '<span class="badge text-bg-danger">Client</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-success">Simulation libre</span>';
                break;
        }
        
        return $status;
    }

    public function satusMetting(Metting $metting)
    {
        $type = $metting->getStatus() ?? '';
        switch ($type) {
            case Metting::STATUS_CANCELLED :
                $status = '<span class="badge text-bg-danger">Annulé</span>';
                break;

            case Metting::STATUS_COMPLETED :
                $status = '<span class="badge text-bg-info">Terminé</span>';
                break;

            case Metting::STATUS_CONFIRMED :
                $status = '<span class="badge text-bg-success">Confirmé</span>';
                break;

            case Metting::STATUS_NOSHOW :
                $status = '<span class="badge text-bg-dark">Non présenté</span>';
                break;

            case Metting::STATUS_PENDING :
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;

            case Metting::STATUS_RESCHEDULED :
                $status = '<span class="badge text-bg-success">Valide</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-primary">En attente</span>';
                break;
        }
        
        return $status;
    }

    public function satusEntreprise(EntrepriseProfile $entreprise)
    {
        $type = $entreprise->getStatus() ?? '';
        switch ($type) {
            case EntrepriseProfile::STATUS_VALID :
                $status = '<span class="badge text-bg-success">Validé</span>';
                break;

            case EntrepriseProfile::STATUS_PENDING :
                $status = '<span class="badge text-bg-info">En attente</span>';
                break;

            case EntrepriseProfile::STATUS_PREMIUM :
                $status = '<span class="badge text-bg-dark">Premium</span>';
                break;

            case EntrepriseProfile::STATUS_BANNED :
                $status = '<span class="badge text-bg-danger">Banni</span>';
                break;
            
            default:
                $status = '<span class="badge text-bg-info">En attente</span>';
                break;
        }
        
        return $status;
    }

}