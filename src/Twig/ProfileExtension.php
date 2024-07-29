<?php

namespace App\Twig;

use DateTime;
use App\Entity\User;
use Twig\TwigFilter;
use DateTimeInterface;
use IntlDateFormatter;
use Twig\TwigFunction;
use App\Entity\Availability;
use App\Entity\Finance\Devise;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Employe;
use App\Entity\ReferrerProfile;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\Favoris;
use App\Entity\Finance\Simulateur;
use App\Entity\Entreprise\JobListing;
use Twig\Extension\AbstractExtension;
use App\Entity\Candidate\TarifCandidat;
use App\Entity\Entreprise\PrimeAnnonce;
use App\Manager\Finance\EmployeManager;
use App\Entity\Entreprise\BudgetAnnonce;
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
            new TwigFunction('getDefaultTarifCandidat', [$this, 'getDefaultTarifCandidat']),
            new TwigFunction('getBudgetAnnonceStr', [$this, 'getBudgetAnnonceStr']),
            new TwigFunction('getPrimeAnnonceStrById', [$this, 'getPrimeAnnonceStrById']),
            new TwigFunction('getBudgetAnnonceStrById', [$this, 'getBudgetAnnonceStrById']),
            new TwigFunction('getAvailabilityStr', [$this, 'getAvailabilityStr']),
        ];
    }

    public function getDefaultTarifCandidat(CandidateProfile $profile): string
    {
        $tarif = '';
        $tarifCandidat = $profile->getTarifCandidat();
        if($tarifCandidat instanceof TarifCandidat){
            $currentTarif = $this->convertTarifToDevise($tarifCandidat, $this->em->getRepository(Devise::class)->find(1));
            $symbole = "€";
            if($currentTarif->getCurrency() instanceof Devise){
                $symbole = $currentTarif->getCurrency()->getSymbole();
            }
            $tarif = round($currentTarif->getMontant(), 2).' '.$symbole.' '.$this->getTypeTarif($currentTarif);
            $simulation = $tarifCandidat->getSimulation();
            if($simulation instanceof Simulateur){
                $simulateur = $this->employeManager->convertSimulationToDevise($simulation, $this->em->getRepository(Devise::class)->find(1));
                $tarif = round($simulateur->getSalaireNet(), 2).' '.$simulateur->getDevise()->getSymbole().' /mois';
            }
        }

        return $tarif;
    }

    public function getBudgetAnnonceStr(JobListing $annonce): string
    {
        $tarif = '';
        $budgetAnnonce = $annonce->getBudgetAnnonce();
        if($budgetAnnonce instanceof BudgetAnnonce){
            $symbole = "€";
            if($budgetAnnonce->getCurrency() instanceof Devise){
                $symbole = $budgetAnnonce->getCurrency()->getSymbole();
            }
            $tarif = round($budgetAnnonce->getMontant(), 2).' '.$symbole;
        }elseif($annonce->getBudget() !== null){
            $tarif = $annonce->getBudget().' €';
        }

        return $tarif;
    }

    public function getBudgetAnnonceStrById(int $id): string
    {
        $tarif = '';
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        if(!$annonce instanceof JobListing){
            return "";
        }
        $budgetAnnonce = $annonce->getBudgetAnnonce();
        if($budgetAnnonce instanceof BudgetAnnonce){
            $symbole = "€";
            if($budgetAnnonce->getCurrency() instanceof Devise){
                $symbole = $budgetAnnonce->getCurrency()->getSymbole();
            }
            $tarif = round($budgetAnnonce->getMontant(), 2).' '.$symbole;
        }elseif($annonce->getBudget() !== null){
            $tarif = $annonce->getBudget().' €';
        }

        return $tarif;
    }

    public function getPrimeAnnonceStrById(int $id): string
    {
        $tarif = '';
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        if(!$annonce instanceof JobListing){
            return "";
        }
        $primeAnnonce = $annonce->getPrimeAnnonce();
        if($primeAnnonce instanceof PrimeAnnonce){
            $symbole = "€";
            if($primeAnnonce->getDevise() instanceof Devise){
                $symbole = $primeAnnonce->getDevise()->getSymbole();
            }
            $tarif = round($primeAnnonce->getMontant(), 2).' '.$symbole;
        }

        return $tarif;
    }

    public function getTarifByCompanyCurrency(CandidateProfile $profile, EntrepriseProfile $company): string
    {
        $tarif = '';
        $currency = $company->getDevise() !== null ? $company->getDevise() : $this->em->getRepository(Devise::class)->find(1);
        $tarifCandidat = $profile->getTarifCandidat();
        if($tarifCandidat instanceof TarifCandidat){
            $currentTarif = $this->convertTarifToDevise($tarifCandidat, $currency);
            $symbole = "€";
            if($currentTarif->getCurrency() instanceof Devise){
                $symbole = $currentTarif->getCurrency()->getSymbole();
            }
            $tarif = round($currentTarif->getMontant(), 2).' '.$symbole.' '.$this->getTypeTarif($currentTarif);
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
        if(!$devise instanceof Devise){
            $devise = $this->em->getRepository(Devise::class)->find(1);
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

    public function getAvailabilityStr(CandidateProfile $candidateProfile): string
    {
        $status = 'Non renseigné';
        $availability = $candidateProfile->getAvailability();
        if($availability instanceof Availability){
            switch ($availability->getNom()) {
                case 'immediate':
                    $status = 'Disponible';
                    break;

                case 'from-date':
                    $status = 'A partir du '. $availability->getDateFin()->format('d/m/Y');
                    break;

                case 'full-time':
                    $status = 'Temps plein';
                    break;

                case 'part-time':
                    $status = 'Temps partiel';
                    break;

                case 'not-available':
                    $status = 'Non disponible';
                    break;
                
                default:
                    $status = 'Non renseigné';
                    break;
            }
        }

        return $status;
    }
}