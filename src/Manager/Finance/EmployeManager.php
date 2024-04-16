<?php

namespace App\Manager\Finance;

use App\Entity\Finance\Employe;
use App\Entity\Finance\Simulateur;
use App\Repository\Finance\DeviseRepository;
use Twig\Environment as Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class EmployeManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private DeviseRepository $deviseRepository,
        private Security $security
    ){        
    }

    public function init(): Employe
    {
        $employe = (new Employe());

        return $employe;
    }

    public function simulate(Simulateur $simulateur): array
    {
        $montantEuro = $this->convertEuroToAriary($simulateur->getSalaireNet(), $simulateur->getTaux());
        $repasEuro = $this->convertEuroToAriary($simulateur->getPrixRepas(), $simulateur->getTaux());
        $deplacementEuro = $this->convertEuroToAriary($simulateur->getPrixDeplacement(), $simulateur->getTaux());
        $connexionEuro = $this->convertEuroToAriary($simulateur->getAvantage()->getPrimeConnexion(), $simulateur->getTaux());
        $fraisProEuro = $this->convertEuroToAriary($simulateur->getAvantage()->getPrimeFonction(), $simulateur->getTaux());
        if($simulateur->getStatus() !== "FREELANCE"){

            return $this->estimationSalaireBrute(
                $simulateur,
                $montantEuro, 
                $simulateur->getNombreEnfant(), 
                $simulateur->getTaux(),
                $repasEuro * $simulateur->getJourRepas(),
                $deplacementEuro * $simulateur->getJourDeplacement(),
                $connexionEuro,
                $fraisProEuro,
                $simulateur->getType(), 
            );
        }

        return $this->estimationSalaireBruteFreelance(
            $simulateur,
            $montantEuro,
            $simulateur->getNombreEnfant(), 
            $simulateur->getTaux(),
            $repasEuro * $simulateur->getJourRepas(),
            $deplacementEuro * $simulateur->getJourDeplacement(),
            $connexionEuro,
            $fraisProEuro,
            $simulateur->getType(), 
        );

    }

    public function estimationSalaireBruteFreelance(
        Simulateur $simulateur, 
        float $salaire_net, 
        int $nbrEnfant, 
        float $tauxDeChange,
        float $fraisRepas,
        float $fraisDeplacement,
        float $fraisConnexion,
        float $fraisProfessionnels,
        string $type
    )
    {
        $salaire_brut_estime_euro = (($salaire_net + $fraisProfessionnels + $fraisDeplacement + $fraisRepas + $fraisConnexion ) / (1 - 0.05));
        $salaire_de_base_euro = ( $salaire_brut_estime_euro - ($fraisProfessionnels + $fraisDeplacement + $fraisRepas + $fraisConnexion ));
        $charge = $salaire_de_base_euro - ($salaire_net ) ;

        return [
            'fraisRepas' => $fraisRepas,
            'fraisDeplacement' => $fraisDeplacement,
            'fraisConnexion' => $fraisConnexion,
            'fraisProfessionnels' => $fraisProfessionnels,
            'irsa_euro' => $this->convertAriaryToEuro($charge, $tauxDeChange),
            'nbrEnfant' => $nbrEnfant,
            'salaire_de_base_ariary' => $salaire_de_base_euro,
            'salaire_de_base_euro' => $this->convertAriaryToEuro($salaire_de_base_euro, $tauxDeChange),
            'salaire_brut_estime_euro' => $this->convertAriaryToEuro($salaire_brut_estime_euro, $tauxDeChange),
            'charge_salariale_euro' => $this->convertAriaryToEuro($charge, $tauxDeChange),
            'salaire_net_euro' => $this->convertAriaryToEuro($salaire_net, $tauxDeChange),
            'facture_total_a_envoyer_euro' => $this->convertAriaryToEuro($this->getFactureTotal($salaire_brut_estime_euro, $simulateur), $tauxDeChange),
            'cout_avant_portage_euro' => $this->convertAriaryToEuro(($salaire_brut_estime_euro), $tauxDeChange),
            'frais_de_portage_euro' => $this->convertAriaryToEuro($this->getFraisPortage($salaire_brut_estime_euro, $simulateur), $tauxDeChange),
            'coworking' => $this->convertAriaryToEuro($this->getCoworking($simulateur), $tauxDeChange),
        ];
    }

    public function estimationSalaireBrute(
        Simulateur $simulateur, 
        float $salaire_net, 
        int $nbrEnfant, 
        float $tauxDeChange,
        float $fraisRepas,
        float $fraisDeplacement,
        float $fraisConnexion,
        float $fraisProfessionnels,
        string $type,
    )
    {
        $seuil = 1; // Seuil de précision de la différence de salaire net
        $max_iterations = 100; // Prévenir la boucle infinie
        $iteration = 1;
        $coworking = 0;
        $ajustement = 100; // Valeur initiale d'ajustement

        $totalFraisSupplementaires = $fraisRepas + $fraisDeplacement + $fraisConnexion + $fraisProfessionnels;
        $salaire_brut_estime = ($salaire_net + $totalFraisSupplementaires) / (1 - 0.30); // Estimation initiale

        while ($iteration < $max_iterations) {
            $cnaps = $this->getCnaps($salaire_brut_estime);
            $ostie = $this->getOstie($salaire_brut_estime);
            $irsa = $this->getIrsa($salaire_brut_estime, $nbrEnfant, $cnaps, $ostie);
            $salaire_net_calcule = $salaire_brut_estime - ($cnaps + $ostie + $irsa);

            if (abs($salaire_net_calcule - $salaire_net) < $seuil) {
                break; // Proche du résultat souhaité
            } else {
                // Ajuster l'estimation du salaire brut
                $salaire_brut_estime += ($salaire_net - $salaire_net_calcule) / 2;
            }

            $iteration++;
        }
        // Supposons que vous ayez le salaire brut estimé à la fin de vos calculs itératifs
        $salaire_brut_final = $salaire_brut_estime; // Résultat de votre boucle itérative

        // Calculer le salaire de base en soustrayant les frais supplémentaires du salaire brut estimé
        $salaire_de_base = $salaire_brut_final - ($fraisRepas + $fraisDeplacement + $fraisConnexion + $fraisProfessionnels);


        return [
            'tauxDeChange' => $tauxDeChange,
            'fraisRepas' => $fraisRepas,
            'fraisDeplacement' => $fraisDeplacement,
            'fraisConnexion' => $fraisConnexion,
            'fraisProfessionnels' => $fraisProfessionnels,
            'nbrEnfant' => $nbrEnfant,
            'tauxDeChange' => $tauxDeChange,
            'ariary' => "------------",
            'irsa_ariary' => $irsa,
            'cnaps_ariary' => $cnaps,
            'ostie_ariary' => $ostie,
            'salaire_de_base_ariary' => $salaire_de_base,
            'salaire_net_ariary' => $salaire_net,
            'salaire_brut_estime_ariary' => $salaire_brut_estime,
            'salaire_net_calcule_ariary' => $salaire_net_calcule,
            'charge_salariale_ariary' => $this->getChargesSalarial($salaire_brut_estime, $nbrEnfant),
            'charges_patronales_ariary' => $this->getChargesPatronales($salaire_brut_estime),
            'cout_avant_portage_ariary' => $this->getChargesPatronales($salaire_brut_estime) + $salaire_brut_estime,
            'frais_de_portage_ariary' => $this->getFraisPortage($salaire_brut_estime, $simulateur),
            'facture_total_a_envoyer_ariary' => $this->getFactureTotal($salaire_brut_estime, $simulateur),
            'ajustement_ariary' => $salaire_net - $salaire_net_calcule,
            'euro' => "------------",
            'irsa_euro' => $this->convertAriaryToEuro($irsa, $tauxDeChange),
            'cnaps_euro' => $this->convertAriaryToEuro($cnaps, $tauxDeChange),
            'ostie_euro' => $this->convertAriaryToEuro($ostie, $tauxDeChange),
            'salaire_de_base_euro' => $this->convertAriaryToEuro($salaire_de_base, $tauxDeChange),
            'salaire_net_euro' => $this->convertAriaryToEuro($salaire_net, $tauxDeChange),
            'salaire_net_calcule_euro' => $this->convertAriaryToEuro($salaire_net_calcule, $tauxDeChange),
            'salaire_brut_estime_euro' => $this->convertAriaryToEuro($salaire_brut_estime, $tauxDeChange),
            'charge_salariale_euro' => $this->convertAriaryToEuro($this->getChargesSalarial($salaire_brut_estime, $nbrEnfant), $tauxDeChange),
            'charges_patronales_euro' => $this->convertAriaryToEuro($this->getChargesPatronales($salaire_brut_estime), $tauxDeChange),
            'cout_avant_portage_euro' => $this->convertAriaryToEuro(($this->getChargesPatronales($salaire_brut_estime) + $salaire_brut_estime), $tauxDeChange),
            'frais_de_portage_euro' => $this->convertAriaryToEuro($this->getFraisPortage($salaire_brut_estime, $simulateur), $tauxDeChange),
            'facture_total_a_envoyer_euro' => $this->convertAriaryToEuro($this->getFactureTotal($salaire_brut_estime, $simulateur), $tauxDeChange),
            'ajustement_euro' => $this->convertAriaryToEuro($salaire_net - $salaire_net_calcule, $tauxDeChange),
            'coworking' => $this->convertAriaryToEuro($this->getCoworking($simulateur), $tauxDeChange),
        ];

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

    private function getCnaps(float $salaire_brut):float
    {
        return $this->getCoeffSocial($salaire_brut);
    }

    private function getOstie(float $salaire_brut):float
    {
        return $this->getCoeffSocial($salaire_brut);
    }

    public function getIrsa(float $salaire_brut, int $nbrEnfant, float $cnaps, float $ostie):float
    {
        return $this->getCoeffIrsa($salaire_brut - $cnaps - $ostie, $nbrEnfant);
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

    public function convertEuroToAriary(?float $montantEuro, float $tauxDeChange): float {
        return $montantEuro * $tauxDeChange;
    }

    public function convertAriaryToEuro(?float $montantAriary, float $tauxDeChange): float {
        return $montantAriary / $tauxDeChange;
    }

    private function getFraisPortage(float $salaire_brut, Simulateur $simulateur):float
    {
        if($simulateur->getStatus() === "FREELANCE"){
            if($salaire_brut < 1500000 ){
                return $salaire_brut * 20 / 100;
            }elseif (1500001 < $salaire_brut and $salaire_brut < 3000000) {
                return $salaire_brut * 15 / 100;
            }elseif (3000001 < $salaire_brut and $salaire_brut < 6000000) {
                return $salaire_brut * 12 / 100;
            }elseif (6000001 < $salaire_brut) {
                return $salaire_brut * 10 / 100;
            }
        }
        if($salaire_brut < 1500000 ){
            return $this->getCoutAvantPortage($salaire_brut) * 20 / 100;
        }elseif (1500001 < $salaire_brut and $salaire_brut < 3000000) {
            return $this->getCoutAvantPortage($salaire_brut) * 15 / 100;
        }elseif (3000001 < $salaire_brut and $salaire_brut < 6000000) {
            return $this->getCoutAvantPortage($salaire_brut) * 12 / 100;
        }elseif (6000001 < $salaire_brut) {
            return $this->getCoutAvantPortage($salaire_brut) * 10 / 100;
        }
    }

    private function getChargesPatronales(float $salaire_brut):float
    {
        if($salaire_brut < 1910400 ){
            return $salaire_brut * 19 / 100;
        }
        return 1910400 * 19 / 100;
    }

    private function getChargesSalarial(float $salaire_brut, int $nbrEnfant):float
    {
        $cnaps = $this->getCnaps($salaire_brut);
        $ostie = $this->getOstie($salaire_brut);
        $irsa = $this->getIrsa($salaire_brut, $nbrEnfant, $cnaps, $ostie);
        
        return $irsa + $ostie + $cnaps;
    }

    private function getCoutAvantPortage(float $salaire_brut):float
    {
        return $this->getChargesPatronales($salaire_brut) + $salaire_brut;
    }

    private function getCoworking(Simulateur $simulateur):float
    {
        $coworking = 0;
        $euro = $this->deviseRepository->findOneBy(['slug' => 'euro']);
        if($simulateur->getType() === "OLONA"){
            $coworking = $this->convertEuroToAriary(150, $euro->getTaux()); 
        }
        return $coworking;
    }

    private function getFactureTotal(float $salaire_brut, Simulateur $simulateur):float
    {
        if($simulateur->getStatus() === "FREELANCE"){
            return $salaire_brut + $this->getFraisPortage($salaire_brut, $simulateur);
        }
        return $salaire_brut + $this->getChargesPatronales($salaire_brut) + $this->getFraisPortage($salaire_brut, $simulateur) + $this->getCoworking($simulateur);
    }

    public function searchEmployes(?string $nom = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($nom == null){
            return $this->em->getRepository(Employe::class)->findBy([], ['id' => 'DESC']);
        }

        if (!empty($nom)) {
            $conditions[] = '(u.nom LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        $qb->select('e')
            ->from('App\Entity\Finance\Employe', 'e')
            ->leftJoin('e.user', 'u')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }
}