<?php

namespace App\Entity\Finance;

use App\Entity\Finance\Contrat;
use App\Repository\Finance\SimulateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SimulateurRepository::class)]
class Simulateur
{    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $salaireNet = null;

    #[ORM\Column]
    private ?float $taux = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreEnfant = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Avantage $avantage = null;

    #[ORM\OneToOne(mappedBy: 'simulateur', cascade: ['persist', 'remove'])]
    private ?Contrat $contrat = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?float $forfait = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'simulateurs', cascade: ['persist', 'remove'])]
    private ?Devise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'simulateurs', cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: true)]
    private ?int $jourRepas = null;

    #[ORM\Column(nullable: true)]
    private ?int $jourDeplacement = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixRepas = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixDeplacement = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $deviseSymbole = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalaireNet(): ?float
    {
        return $this->salaireNet;
    }

    public function setSalaireNet(float $salaireNet): static
    {
        $this->salaireNet = $salaireNet;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(float $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getNombreEnfant(): ?int
    {
        return $this->nombreEnfant;
    }

    public function setNombreEnfant(?int $nombreEnfant): static
    {
        $this->nombreEnfant = $nombreEnfant;

        return $this;
    }

    public function getAvantage(): ?Avantage
    {
        return $this->avantage;
    }

    public function setAvantage(?Avantage $avantage): static
    {
        $this->avantage = $avantage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getForfait(): ?float
    {
        return $this->forfait;
    }

    public function setForfait(?float $forfait): static
    {
        $this->forfait = $forfait;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
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

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        // unset the owning side of the relation if necessary
        if ($contrat === null && $this->contrat !== null) {
            $this->contrat->setCandidat(null);
        }

        // set the owning side of the relation if necessary
        if ($contrat !== null && $contrat->getCandidat() !== $this) {
            $contrat->setCandidat($this);
        }

        $this->contrat = $contrat;

        return $this;
    }

    public function getJourRepas(): ?int
    {
        return $this->jourRepas;
    }

    public function setJourRepas(?int $jourRepas): static
    {
        $this->jourRepas = $jourRepas;

        return $this;
    }

    public function getJourDeplacement(): ?int
    {
        return $this->jourDeplacement;
    }

    public function setJourDeplacement(?int $jourDeplacement): static
    {
        $this->jourDeplacement = $jourDeplacement;

        return $this;
    }

    public function getPrixRepas(): ?float
    {
        return $this->prixRepas;
    }

    public function setPrixRepas(?float $prixRepas): static
    {
        $this->prixRepas = $prixRepas;

        return $this;
    }

    public function getPrixDeplacement(): ?float
    {
        return $this->prixDeplacement;
    }

    public function setPrixDeplacement(?float $prixDeplacement): static
    {
        $this->prixDeplacement = $prixDeplacement;

        return $this;
    }

    public function getDeviseSymbole(): ?string
    {
        return $this->deviseSymbole;
    }

    public function setDeviseSymbole(?string $deviseSymbole): static
    {
        $this->deviseSymbole = $deviseSymbole;

        return $this;
    }
}
