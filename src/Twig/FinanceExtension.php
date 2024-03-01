<?php

namespace App\Twig;

use DateTime;
use App\Entity\User;
use Twig\TwigFilter;
use DateTimeInterface;
use IntlDateFormatter;
use Twig\TwigFunction;
use App\Entity\ReferrerProfile;
use App\Entity\Referrer\Referral;
use App\Entity\Entreprise\JobListing;
use App\Entity\Finance\Contrat;
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

class FinanceExtension extends AbstractExtension
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
            new TwigFilter('contratStatus', [$this, 'contratStatus']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getPeriod', [$this, 'getPeriod']),
            new TwigFunction('getConge', [$this, 'getConge']),
            new TwigFunction('getCnaps', [$this, 'getCnaps']),
            new TwigFunction('getOstie', [$this, 'getOstie']),
            new TwigFunction('getIrsa', [$this, 'getIrsa']),
            new TwigFunction('getAbsence', [$this, 'getAbsence']),
            new TwigFunction('getHS30', [$this, 'getHS30']),
            new TwigFunction('getHS40', [$this, 'getHS40']),
            new TwigFunction('getHS50', [$this, 'getHS50']),
            new TwigFunction('getHS100', [$this, 'getHS100']),
            new TwigFunction('getHS130', [$this, 'getHS130']),
            new TwigFunction('getHS150', [$this, 'getHS150']),
            new TwigFunction('getCongePris', [$this, 'getCongePris']),
            new TwigFunction('getCongePaye', [$this, 'getCongePaye']),
            new TwigFunction('getTotalMoins', [$this, 'getTotalMoins']),
            new TwigFunction('getSalaireBrut', [$this, 'getSalaireBrut']),
            new TwigFunction('getSalaireNet', [$this, 'getSalaireNet']),
            new TwigFunction('getSalaireNetRounded', [$this, 'getSalaireNetRounded']),
            new TwigFunction('convertToDevise', [$this, 'convertToDevise']),
            new TwigFunction('convertToAriary', [$this, 'convertToAriary']),
            new TwigFunction('getFraisPortage', [$this, 'getFraisPortage']),
        ];
    }

    public function contratStatus(Contrat $contrat):string
    {
        $button = "";
        $status = $contrat->getStatus();
        switch ($status) {
            case 'APPROVED':
                $button = '<button type="button" class="btn btn-primary rounded-pill px-5" disabled><i class="bi bi-check2-circle"></i> Approuvé</button>';
                break;

            case 'ACTIVE':
                $button = '<button type="button" class="btn btn-success rounded-pill px-5" disabled><i class="bi bi-check2-circle"></i> Actif</button>';
                break;
                
            case 'EXPIRED':
                $button = '<button type="button" class="btn btn-outline-dark disabled rounded-pill px-5"><i class="bi bi-hourglass-bottom mx-2"></i>Expiré</button>';
                break;
                
            case 'ARCHIVED':
                $button = '<button type="button" class="btn btn-info disabled rounded-pill px-5"><i class="bi bi-info-circle-fill"></i> Resilié</button>';
                break;
                
            case 'SUSPENDED':
                $button = '<button type="button" class="btn btn-danger disabled rounded-pill px-5"><i class="bi bi-info-circle-fill"></i> Suspendu</button>';
                break;
                
            case 'UNFULFILLED':
                $button = '<button type="button" class="btn btn-dark disabled rounded-pill px-5"><i class="bi bi-info-circle-fill"></i> Non exécuté</button>';
                break;
                
            case 'RENEWED':
                $button = '<button type="button" class="btn btn-success disabled rounded-pill px-5"><i class="bi bi-info-circle-fill"></i> Renouvelé</button>';
                break;
            
            default:
                $button = '<button type="button" class="btn btn-outline-success rounded-pill px-5 disabled"><i class="bi bi-hourglass-split"></i> En attente de réponse</button>';
                break;
        }

        return $button;
    }

    public function contratStatusBadge(Contrat $contrat):string
    {
        $button = "";
        $status = $contrat->getStatus();
        switch ($status) {
            case 'APPROVED':
                $button = '<span class="badge rounded-pill text-bg-success">Approuvé</span>';
                break;

            case 'ACTIVE':
                $button = '<span class="badge rounded-pill text-bg-success">Actif</span>';
                break;
                
            case 'EXPIRED':
                $button = '<span class="badge rounded-pill text-bg-info">Expiré</span>';
                break;
                
            case 'ARCHIVED':
                $button = '<span class="badge rounded-pill text-bg-info">Resilié</span>';
                break;
                
            case 'SUSPENDED':
                $button = '<span class="badge rounded-pill text-bg-danger">Suspendu</span>';
                break;
                
            case 'UNFULFILLED':
                $button = '<span class="badge rounded-pill text-bg-danger">Non exécuté</span>';
                break;
                
            case 'RENEWED':
                $button = '<span class="badge rounded-pill text-bg-warning">Renouvelé</span>';
                break;
            
            default:
                $button = '<span class="badge rounded-pill text-bg-dark">En attente</span>';
                break;
        }

        return $button;
    }

    private function getCoeffIrsa(float $salaireBrutOstisCnaps, int $nbrEnfant = 0) : float
    {
        $irsa = 0;
        if($salaireBrutOstisCnaps < 350000){
            $irsa = 0;
        }elseif (350001 < $salaireBrutOstisCnaps and $salaireBrutOstisCnaps < 400000) {
            $irsa = ($salaireBrutOstisCnaps - 35001) * 0.05;
        }elseif (400001 < $salaireBrutOstisCnaps and $salaireBrutOstisCnaps < 500000) {
            $irsa = (($salaireBrutOstisCnaps - 400001) * 0.1) + (400000 - 35001) * 0.05;
        }elseif (500001 < $salaireBrutOstisCnaps and $salaireBrutOstisCnaps < 600000) {
            $irsa = ($salaireBrutOstisCnaps - 500001) * 0.15 + (500000 - 400001) * 0.1 + (400000 - 35000) * 0.05;
        }else{
            $irsa = ($salaireBrutOstisCnaps - 600001) * 0.2 - 2000 * $nbrEnfant  + (600000 - 500001) * 0.15 + (500000 - 400001) * 0.1 + (400000 - 350001) * 0.05;
        }

        return $irsa;
    }

    private function getCoeffSocial(float $salaireBrut) : float
    {
        $coeff = 0;
        if($salaireBrut < 1910400){
            $coeff = $salaireBrut / 100;
        }else{
            $coeff = 19104;
        }

        return $coeff;
    }

    public function getPeriod(): string 
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::NONE, 
                                    null, IntlDateFormatter::GREGORIAN, 'MMM-yy');
        return $formatter->format(new DateTime());
    }

    public function getConge(?DateTimeInterface $dateEmbauche = null): float
    {
        if($dateEmbauche == null){
            return 0;
        }
        $firstDayOfCurrentMonth = new DateTime('first day of this month');
        $firstDayOfLastMonth = $firstDayOfCurrentMonth->modify('-1 month');
        $diff = $dateEmbauche->diff($firstDayOfLastMonth);

        // Calculer la différence entre les deux dates
        $totalMonths = $diff->y * 12 + $diff->m;

        return (float) $totalMonths;
    }

    public function getCnaps(Employe $employe):float
    {
        return $this->getCoeffSocial($this->getSalaireBrut($employe));
    }

    public function getOstie(Employe $employe):float
    {
        return $this->getCoeffSocial($this->getSalaireBrut($employe));
    }

    public function getIrsa(Employe $employe):float
    {
        $salaireBrut = $this->getSalaireBrut($employe);
        $nbrEnfant = $employe->getNombreEnfants();
        $cnaps = $this->getCnaps($employe);
        $ostie = $this->getOstie($employe);

        return $this->getCoeffIrsa($salaireBrut - $cnaps - $ostie, $nbrEnfant);
    }

    public function getAbsence(Employe $employe):float
    {
        $absence = $employe->getAvantage()->getAbsence();

        return $employe->getSalaireBase() * $absence / 30;
    }

    public function getHS30(Employe $employe):float
    {
        $hs30 = $employe->getAvantage()->getHS30();

        return ($employe->getSalaireBase() / 173.33 * 30/100 ) * $hs30;
    }

    public function getHS40(Employe $employe):float
    {
        $hs40 = $employe->getAvantage()->getHS40();

        return ($employe->getSalaireBase() / 173.33 * 1.4 ) * $hs40;
    }

    public function getHS50(Employe $employe):float
    {
        $hs50 = $employe->getAvantage()->getHS50();

        return ($employe->getSalaireBase() / 173.33 * 1.5 ) * $hs50;
    }

    public function getHS100(Employe $employe):float
    {
        $hs100 = $employe->getAvantage()->getHn();

        return ($employe->getSalaireBase() / 173.33 * 2 ) * $hs100;
    }

    public function getHS130(Employe $employe):float
    {
        $hs130 = $employe->getAvantage()->getHs130();

        return ($employe->getSalaireBase() / 173.33 * 1.3 ) * $hs130;
    }

    public function getHS150(Employe $employe):float
    {
        $hs150 = $employe->getAvantage()->getHs150();

        return ($employe->getSalaireBase() / 173.33 * 1.5 ) * $hs150;
    }

    public function getRepas(Employe $employe, $prix = 3500):float
    {
        return $employe->getAvantage()->getRepas() * $prix;
    }

    public function getDeplacement(Employe $employe):float
    {
        return $employe->getAvantage()->getDeplacement() * 2500;
    }

    public function getCongePaye(Employe $employe):float
    {
        return $employe->getAvantage()->getMoySur12() / 24 * $employe->getAvantage()->getCongePaye();
    }

    public function getCongePris(Employe $employe):float
    {
        return $employe->getSalaireBase() / 30 * $employe->getAvantage()->getCongePaye();
    }

    public function getSalaireBrut(Employe $employe):float
    {
        $salaireBase = $employe->getSalaireBase();
        $primeFonction = $employe->getAvantage()->getPrimeFonction();
        $hs = $this->getHS30($employe) + $this->getHS40($employe) + $this->getHS50($employe) + $this->getHS100($employe) + $this->getHS130($employe) + $this->getHS150($employe);
        $avantage = $this->getRepas($employe) + $this->getDeplacement($employe) + $employe->getAvantage()->getPrimeConnexion();
        $conges = $this->getCongePaye($employe);
        $congesPris = $this->getCongePris($employe);
        $absence = $this->getAbsence($employe);

        return $salaireBase + $hs + $primeFonction + $avantage + $conges - ($congesPris + $absence);
    }

    public function getSalaireNet(Employe $employe):float
    {
        $totalMoins = $this->getTotalMoins($employe);
        $salaireBrut = $this->getSalaireBrut($employe);

        return $salaireBrut - $totalMoins;
    }

    public function getSalaireNetRounded(Employe $employe):float
    {
        $totalMoins = $this->getTotalMoins($employe);
        $salaireBrut = $this->getSalaireBrut($employe);

        return round($salaireBrut - $totalMoins);
    }

    public function getTotalMoins(Employe $employe):float
    {
        $primeAvance = $employe->getAvantage()->getPrimeAvance15();
        $avanceSpeciale = $employe->getAvantage()->getAvanceSpeciale();
        $irsa = $this->getIrsa($employe);
        $cnaps = $this->getCnaps($employe);
        $ostie = $this->getOstie($employe);

        return $primeAvance + $avanceSpeciale + $irsa + $cnaps + $ostie;
    }

    public function convertToDevise(float $amount, Simulateur $simulateur):float
    {
        return round($this->employeManager->convertAriaryToEuro($amount, $simulateur->getTaux())) ;
    }

    public function convertToAriary(float $amount, Simulateur $simulateur):float
    {
        return round($this->employeManager->convertEuroToAriary($amount, $simulateur->getTaux())) ;
    }

    public function getFraisPortage(Simulateur $simulateur):float
    {
        $results = $this->employeManager->simulate($simulateur);

        return round($results['frais_de_portage_euro']) ;
    }

}