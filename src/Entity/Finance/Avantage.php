<?php

namespace App\Entity\Finance;

use App\Repository\Finance\AvantageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvantageRepository::class)]
class Avantage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'avantage', cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: true)]
    private ?int $absence = null;

    #[ORM\Column(nullable: true)]
    private ?int $hs30 = null;

    #[ORM\Column(nullable: true)]
    private ?int $hs40 = null;

    #[ORM\Column(nullable: true)]
    private ?int $hs50 = null;

    #[ORM\Column(nullable: true)]
    private ?int $hn = null;

    #[ORM\Column(nullable: true)]
    private ?int $congePaye = null;

    #[ORM\Column(nullable: true)]
    private ?int $congePris = null;

    #[ORM\Column(nullable: true)]
    private ?float $primeFonction = null;

    #[ORM\Column(nullable: true)]
    private ?float $primeConnexion = null;

    #[ORM\Column(nullable: true)]
    private ?float $rappel = null;

    #[ORM\Column(nullable: true)]
    private ?float $repas = null;

    #[ORM\Column(nullable: true)]
    private ?float $deplacement = null;

    #[ORM\Column(nullable: true)]
    private ?float $allocationConge = null;

    #[ORM\Column(nullable: true)]
    private ?float $preavis = null;

    #[ORM\Column(nullable: true)]
    private ?float $primeAvance15 = null;

    #[ORM\Column(nullable: true)]
    private ?float $avanceSpeciale = null;

    #[ORM\Column(nullable: true)]
    private ?float $choixDeduction = null;

    #[ORM\Column(nullable: true)]
    private ?float $salaireBrut = null;

    #[ORM\Column(nullable: true)]
    private ?float $moySur12 = null;

    #[ORM\Column(nullable: true)]
    private ?bool $freelance = null;

    #[ORM\Column(nullable: true)]
    private ?int $hs130 = null;

    #[ORM\Column(nullable: true)]
    private ?int $hs150 = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;

        return $this;
    }

    public function getAbsence(): ?int
    {
        return $this->absence;
    }

    public function setAbsence(?int $absence): static
    {
        $this->absence = $absence;

        return $this;
    }

    public function getHs30(): ?int
    {
        return $this->hs30;
    }

    public function setHs30(?int $hs30): static
    {
        $this->hs30 = $hs30;

        return $this;
    }

    public function getHs40(): ?int
    {
        return $this->hs40;
    }

    public function setHs40(?int $hs40): static
    {
        $this->hs40 = $hs40;

        return $this;
    }

    public function getHs50(): ?int
    {
        return $this->hs50;
    }

    public function setHs50(?int $hs50): static
    {
        $this->hs50 = $hs50;

        return $this;
    }

    public function getHn(): ?int
    {
        return $this->hn;
    }

    public function setHn(?int $hn): static
    {
        $this->hn = $hn;

        return $this;
    }

    public function getCongePaye(): ?int
    {
        return $this->congePaye;
    }

    public function setCongePaye(?int $congePaye): static
    {
        $this->congePaye = $congePaye;

        return $this;
    }

    public function getCongePris(): ?int
    {
        return $this->congePris;
    }

    public function setCongePris(?int $congePris): static
    {
        $this->congePris = $congePris;

        return $this;
    }

    public function getPrimeFonction(): ?float
    {
        return $this->primeFonction;
    }

    public function setPrimeFonction(?float $primeFonction): static
    {
        $this->primeFonction = $primeFonction;

        return $this;
    }

    public function getPrimeConnexion(): ?float
    {
        return $this->primeConnexion;
    }

    public function setPrimeConnexion(?float $primeConnexion): static
    {
        $this->primeConnexion = $primeConnexion;

        return $this;
    }

    public function getRappel(): ?float
    {
        return $this->rappel;
    }

    public function setRappel(?float $rappel): static
    {
        $this->rappel = $rappel;

        return $this;
    }

    public function getRepas(): ?float
    {
        return $this->repas;
    }

    public function setRepas(?float $repas): static
    {
        $this->repas = $repas;

        return $this;
    }

    public function getDeplacement(): ?float
    {
        return $this->deplacement;
    }

    public function setDeplacement(?float $deplacement): static
    {
        $this->deplacement = $deplacement;

        return $this;
    }

    public function getAllocationConge(): ?float
    {
        return $this->allocationConge;
    }

    public function setAllocationConge(?float $allocationConge): static
    {
        $this->allocationConge = $allocationConge;

        return $this;
    }

    public function getPreavis(): ?float
    {
        return $this->preavis;
    }

    public function setPreavis(?float $preavis): static
    {
        $this->preavis = $preavis;

        return $this;
    }

    public function getPrimeAvance15(): ?float
    {
        return $this->primeAvance15;
    }

    public function setPrimeAvance15(?float $primeAvance15): static
    {
        $this->primeAvance15 = $primeAvance15;

        return $this;
    }

    public function getAvanceSpeciale(): ?float
    {
        return $this->avanceSpeciale;
    }

    public function setAvanceSpeciale(?float $avanceSpeciale): static
    {
        $this->avanceSpeciale = $avanceSpeciale;

        return $this;
    }

    public function getChoixDeduction(): ?float
    {
        return $this->choixDeduction;
    }

    public function setChoixDeduction(?float $choixDeduction): static
    {
        $this->choixDeduction = $choixDeduction;

        return $this;
    }

    public function getSalaireBrut(): ?float
    {
        return $this->salaireBrut;
    }

    public function setSalaireBrut(?float $salaireBrut): static
    {
        $this->salaireBrut = $salaireBrut;

        return $this;
    }

    public function getMoySur12(): ?float
    {
        return $this->moySur12;
    }

    public function setMoySur12(?float $moySur12): static
    {
        $this->moySur12 = $moySur12;

        return $this;
    }

    public function isFreelance(): ?bool
    {
        return $this->freelance;
    }

    public function setFreelance(?bool $freelance): static
    {
        $this->freelance = $freelance;

        return $this;
    }

    public function getHs130(): ?int
    {
        return $this->hs130;
    }

    public function setHs130(?int $hs130): static
    {
        $this->hs130 = $hs130;

        return $this;
    }

    public function getHs150(): ?int
    {
        return $this->hs150;
    }

    public function setHs150(?int $hs150): static
    {
        $this->hs150 = $hs150;

        return $this;
    }
}
