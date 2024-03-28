<?php

namespace App\Manager;

use App\Entity\Finance\Simulateur;
use App\Repository\Finance\DeviseRepository;

class SimulateurEntrepriseManager
{
    public function __construct(
        private DeviseRepository $deviseRepository,
    ){}  

    public function simulate(Simulateur $simulateur): array
    {
        $montant = $this->convertDeviseToAriary($simulateur->getSalaireNet(), $simulateur->getTaux());
        $primeNet = $this->convertDeviseToAriary($simulateur->getPrimeNet(), $simulateur->getTaux());
        $repas = $this->convertDeviseToAriary($simulateur->getPrixRepas(), $simulateur->getTaux());
        $deplacement = $this->convertDeviseToAriary($simulateur->getPrixDeplacement(), $simulateur->getTaux());
        $connexion = $this->convertDeviseToAriary($simulateur->getAvantage()->getPrimeConnexion(), $simulateur->getTaux());
        $fraisPro = $this->convertDeviseToAriary($simulateur->getAvantage()->getPrimeFonction(), $simulateur->getTaux());
        if($simulateur->getStatus() !== "FREELANCE"){

            return $this->estimationSalaireBrute(
                $simulateur,
                $montant, 
                $primeNet, 
                $simulateur->getNombreEnfant(), 
                $simulateur->getTaux(),
                $repas * $simulateur->getJourRepas(),
                $deplacement * $simulateur->getJourDeplacement(),
                $connexion,
                $fraisPro,
                $simulateur->getType(), 
            );
        }

        return $this->estimationSalaireBruteFreelance(
            $simulateur,
            $montant,
            $primeNet,
            $simulateur->getNombreEnfant(), 
            $simulateur->getTaux(),
            $repas * $simulateur->getJourRepas(),
            $deplacement * $simulateur->getJourDeplacement(),
            $connexion,
            $fraisPro,
            $simulateur->getType(), 
        );
    }

    public function convertDeviseToAriary(?float $montantDevise, float $tauxDeChange): float {
        return $montantDevise * $tauxDeChange;
    }

    public function convertAriaryToDevise(?float $montantAriary, float $tauxDeChange): float {
        return $montantAriary / $tauxDeChange;
    }

    public function estimationSalaireBruteFreelance(
        Simulateur $simulateur, 
        float $salaire_net, 
        float $prime_net, 
        int $nbrEnfant, 
        float $tauxDeChange,
        float $fraisRepas,
        float $fraisDeplacement,
        float $fraisConnexion,
        float $fraisProfessionnels,
        string $type
    )
    {
        $salaire_brut_estime_euro = (($salaire_net + $prime_net) / (1 - 0.05));
        $salaire_de_base_euro = ( $salaire_brut_estime_euro );
        $charge = $salaire_brut_estime_euro - ($salaire_net + $prime_net) ;

        return [
            'fraisRepas' => $fraisRepas,
            'fraisDeplacement' => $fraisDeplacement,
            'fraisConnexion' => $fraisConnexion,
            'fraisProfessionnels' => $fraisProfessionnels,
            'irsa_euro' => $this->convertAriaryToDevise($charge, $tauxDeChange),
            'nbrEnfant' => $nbrEnfant,
            'salaire_de_base_euro' => $this->convertAriaryToDevise($salaire_de_base_euro, $tauxDeChange),
            'salaire_brut_estime_euro' => $this->convertAriaryToDevise($salaire_de_base_euro, $tauxDeChange),
            'charge_salariale_euro' => $this->convertAriaryToDevise($charge, $tauxDeChange),
            'salaire_net_euro' => $this->convertAriaryToDevise($salaire_net, $tauxDeChange),
            'prime_net_euro' => $this->convertAriaryToDevise($prime_net, $tauxDeChange),
            'facture_total_a_envoyer_euro' => $this->convertAriaryToDevise($this->getFactureTotalFreelance($salaire_brut_estime_euro, $simulateur), $tauxDeChange),
            'cout_avant_portage_euro' => $this->convertAriaryToDevise(($salaire_brut_estime_euro), $tauxDeChange),
            'frais_de_portage_euro' => $this->convertAriaryToDevise($this->getFraisPortage($salaire_brut_estime_euro, $simulateur), $tauxDeChange),
            'coworking' => $this->convertAriaryToDevise($this->getCoworking($simulateur), $tauxDeChange),
            'option_after_6_month' => $this->convertAriaryToDevise($this->getCoutOptionApres6Mois($salaire_brut_estime_euro), $tauxDeChange),
        ];
    }

    public function estimationSalaireBrute(
        Simulateur $simulateur, 
        float $salaire_net, 
        float $prime_net, 
        int $nbrEnfant, 
        float $tauxDeChange,
        float $fraisRepas,
        float $fraisDeplacement,
        float $fraisConnexion,
        float $fraisProfessionnels,
        string $type,
    )
    {
        $seuil = 1; 
        $max_iterations = 100; 
        $max_iterations_base = 100; 
        $iteration = 1;
        $iteration_base = 1;
        $coworking = 0;
        $ajustement = 100; 

        $totalFraisSupplementaires = $fraisRepas + $fraisDeplacement + $fraisConnexion + $fraisProfessionnels;
        $salaire_brut_estime = ($salaire_net + $prime_net + $totalFraisSupplementaires) / (1 - 0.30); // Estimation initiale
        while ($iteration < $max_iterations) {
            $cnaps = $this->getCnaps($salaire_brut_estime);
            $ostie = $this->getOstie($salaire_brut_estime);
            $irsa = $this->getIrsa($salaire_brut_estime, $nbrEnfant, $cnaps, $ostie);
            $salaire_net_calcule = $salaire_brut_estime - ($cnaps + $ostie + $irsa + $prime_net);

            if (abs($salaire_net_calcule - $salaire_net) < 1) {
                break; // Proche du résultat souhaité
            } else {
                $salaire_brut_estime += ($salaire_net - $salaire_net_calcule) / 2;
            }

            $iteration++;
        }
        
        $salaire_brut_final = $salaire_brut_estime; // Résultat de votre boucle itérative
        
        $real_salaire_brut_estime = ($salaire_net + $prime_net + $totalFraisSupplementaires) / (1 - 0.30); // Estimation initiale
        while ($iteration_base < $max_iterations_base) {
            $cnaps1 = $this->getCnaps($real_salaire_brut_estime);
            $ostie1 = $this->getOstie($real_salaire_brut_estime);
            $irsa1 = $this->getIrsa($real_salaire_brut_estime, $nbrEnfant, $cnaps1, $ostie1);
            $real_salaire_net_calcule = $real_salaire_brut_estime - ($cnaps1 + $ostie1 + $irsa1);

            if (abs($real_salaire_net_calcule - $salaire_net) < $seuil) {
                break; // Proche du résultat souhaité
            } else {
                $real_salaire_brut_estime += ($salaire_net - $real_salaire_net_calcule) / 2;
            }

            $iteration_base++;
        }
        
        $salaire_de_base = $real_salaire_brut_estime - ($fraisRepas + $fraisDeplacement + $fraisConnexion + $fraisProfessionnels);
        
        return [
            'tauxDeChange' => $tauxDeChange,
            'fraisRepas' => $fraisRepas,
            'fraisDeplacement' => $fraisDeplacement,
            'fraisConnexion' => $fraisConnexion,
            'fraisProfessionnels' => $fraisProfessionnels,
            'nbrEnfant' => $nbrEnfant,
            'ariary' => "------------",
            'irsa_ariary' => $irsa,
            'cnaps_ariary' => $cnaps,
            'ostie_ariary' => $ostie,
            'salaire_de_base_ariary' => $salaire_de_base,
            'salaire_net_ariary' => $salaire_net,
            'prime_net_ariary' => $prime_net,
            'salaire_brut_estime_ariary' => $salaire_brut_final,
            'salaire_net_calcule_ariary' => $salaire_net_calcule,
            'charge_salariale_ariary' => $this->getChargesSalarial($salaire_brut_final, $nbrEnfant),
            'charges_patronales_ariary' => $this->getChargesPatronales($salaire_brut_final),
            'cout_avant_portage_ariary' => $this->getChargesPatronales($salaire_brut_final) + $salaire_brut_final,
            'frais_de_portage_ariary' => $this->getFraisPortage($salaire_brut_final, $simulateur),
            'facture_total_a_envoyer_ariary' => $this->getFactureTotal($salaire_brut_final, $simulateur),
            'ajustement_ariary' => $salaire_net - $salaire_net_calcule,
            'euro' => "------------",
            'irsa_euro' => $this->convertAriaryToDevise($irsa, $tauxDeChange),
            'cnaps_euro' => $this->convertAriaryToDevise($cnaps, $tauxDeChange),
            'ostie_euro' => $this->convertAriaryToDevise($ostie, $tauxDeChange),
            'salaire_de_base_euro' => $this->convertAriaryToDevise($salaire_de_base, $tauxDeChange),
            'salaire_net_euro' => $this->convertAriaryToDevise($salaire_net, $tauxDeChange),
            'prime_net_euro' => $this->convertAriaryToDevise($prime_net, $tauxDeChange),
            'salaire_net_calcule_euro' => $this->convertAriaryToDevise($salaire_net_calcule, $tauxDeChange),
            'salaire_brut_estime_euro' => $this->convertAriaryToDevise($salaire_brut_final, $tauxDeChange),
            'charge_salariale_euro' => $this->convertAriaryToDevise($this->getChargesSalarial($salaire_brut_final, $nbrEnfant), $tauxDeChange),
            'charges_patronales_euro' => $this->convertAriaryToDevise($this->getChargesPatronales($salaire_brut_final), $tauxDeChange),
            'cout_avant_portage_euro' => $this->convertAriaryToDevise(($this->getChargesPatronales($salaire_brut_final) + $salaire_brut_final), $tauxDeChange),
            'frais_de_portage_euro' => $this->convertAriaryToDevise($this->getFraisPortage($salaire_brut_final, $simulateur), $tauxDeChange),
            'facture_total_a_envoyer_euro' => $this->convertAriaryToDevise($this->getFactureTotal($salaire_brut_final, $simulateur), $tauxDeChange),
            'ajustement_euro' => $this->convertAriaryToDevise($salaire_net - $salaire_net_calcule, $tauxDeChange),
            'coworking' => $this->convertAriaryToDevise($this->getCoworking($simulateur), $tauxDeChange),
            'option_after_6_month' => $this->convertAriaryToDevise($this->getCoutOptionApres6Mois($salaire_brut_final), $tauxDeChange),
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

    private function getFraisPortage(float $salaire_brut, Simulateur $simulateur):float
    {
        if($simulateur->getStatus() === "FREELANCE"){
            if($salaire_brut < 1500000 ){
                return $salaire_brut * 20 / 100;
            }elseif (1500001 < $salaire_brut and $salaire_brut < 3000000) {
                return $salaire_brut * 15 / 100;
            }elseif (3000001 < $salaire_brut and $salaire_brut < 6000000) {
                return $salaire_brut * 8 / 100;
            }elseif (6000001 < $salaire_brut) {
                return $salaire_brut * 8 / 100;
            }
        }
        if($salaire_brut < 1500000 ){
            return $this->getCoutAvantPortage($salaire_brut) * 20 / 100;
        }elseif (1500001 < $salaire_brut and $salaire_brut < 3000000) {
            return $this->getCoutAvantPortage($salaire_brut) * 15 / 100;
        }elseif (3000001 < $salaire_brut and $salaire_brut < 6000000) {
            return $this->getCoutAvantPortage($salaire_brut) * 8 / 100;
        }elseif (6000001 < $salaire_brut) {
            return $this->getCoutAvantPortage($salaire_brut) * 8 / 100;
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

    private function getCoutOptionApres6Mois(float $salaire_brut):float
    {
        return $salaire_brut * 12 * 15 / 100;
    }

    private function getCoworking(Simulateur $simulateur):float
    {
        $coworking = 0;
        $euro = $this->deviseRepository->findOneBy(['slug' => 'euro']);
        if($simulateur->getType() === "OLONA"){
            $coworking = $this->convertDeviseToAriary(150, $euro->getTaux()); 
        }
        return $coworking;
    }

    private function getPrimeNet(Simulateur $simulateur):float
    {
        $primeNet = 0;
        if($simulateur->getPrimeNet() !== null){
            $primeNet = $this->convertDeviseToAriary($simulateur->getPrimeNet(), $simulateur->getTaux()); 
        }

        return $primeNet;
    }

    private function getFactureTotal(float $salaire_brut, Simulateur $simulateur):float
    {
        return $salaire_brut + $this->getChargesPatronales($salaire_brut) + $this->getFraisPortage($salaire_brut, $simulateur) + $this->getCoworking($simulateur);
    }

    private function getFactureTotalFreelance(float $salaire_brut, Simulateur $simulateur):float
    {
        return $salaire_brut + $this->getFraisPortage($salaire_brut, $simulateur) + $this->getCoworking($simulateur);
    }
}
