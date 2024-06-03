<?php

namespace App\Twig;

use App\Entity\Candidate\TarifCandidat;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\Favoris;
use DateTime;
use App\Entity\User;
use Twig\TwigFilter;
use DateTimeInterface;
use IntlDateFormatter;
use Twig\TwigFunction;
use App\Entity\ReferrerProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Devise;
use App\Entity\Finance\Employe;
use App\Entity\Finance\Simulateur;
use App\Manager\Finance\EmployeManager;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProfileExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private EmployeManager $employeManager,
        private UrlGeneratorInterface $urlGenerator,
        private ReferrerProfileRepository $referrerProfileRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('contratStatusBadge', [$this, 'contratStatusBadge']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getTarifByCompanyCurrency', [$this, 'getTarifByCompanyCurrency']),
        ];
    }

    public function getTarifByCompanyCurrency(CandidateProfile $profile, EntrepriseProfile $company): string
    {
        $tarif = '';
        $currency = $company->getDevise() !== null ? $company->getDevise() : $this->em->getRepository(Devise::class)->find(1);
        $tarifCandidat = $profile->getTarifCandidat();
        if($tarifCandidat instanceof TarifCandidat){
            $currentTarif = $this->convertTarifToDevise($tarifCandidat, $currency);
            $tarif = round($currentTarif->getMontant(), 2).' '.$currentTarif->getCurrency()->getSymbole().' '.$this->getTypeTarif($currentTarif);
            $simulation = $tarifCandidat->getSimulation();
            if($simulation instanceof Simulateur){
                $simulateur = $this->employeManager->convertSimulationToDevise($simulation, $currency);
                $tarif = round($simulateur->getSalaireNet(), 2).' '.$simulateur->getDevise()->getSymbole().' /mois';
            }
        }

        return $tarif;
    }

    private function convertTarifToDevise(TarifCandidat $tarif, Devise $devise): TarifCandidat
    {
        $currentDevise = $tarif->getCurrency();
        if(!$currentDevise instanceof Devise){
            $currentDevise = $this->em->getRepository(Devise::class)->find(1);
        }    
        if ($currentDevise != $devise) {
            $currentTaux = $currentDevise->getTaux();
            $newTaux = $devise->getTaux();
    
            $tarif->setCurrency($devise);
            $tarif->setMontant($tarif->getMontant() * $currentTaux / $newTaux);
        }
        return $tarif;
    }

    private function getTypeTarif(TarifCandidat $tarif): string
    {
        $type = ' / mois';
        switch ($tarif->getTypeTarif()) {
            case TarifCandidat::TYPE_HOURLY :
                $type = ' / heure';
                break;
            
            case TarifCandidat::TYPE_DAILY :
                $type = ' / jour';
                break;

            case TarifCandidat::TYPE_MONTHLY :
                $type = ' / mois';
                break;
        }

        return $type;
    }
}