<?php

namespace App\Entity\Finance;

use App\Repository\Finance\SalaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
class Salaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantBrut = null;

    #[ORM\Column(nullable: true)]
    private ?float $cotisationSocial = null;

    #[ORM\Column(nullable: true)]
    private ?float $irsa = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantNet = null;

    #[ORM\OneToOne(mappedBy: 'salaire', cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantBrut(): ?float
    {
        return $this->montantBrut;
    }

    public function setMontantBrut(?float $montantBrut): static
    {
        $this->montantBrut = $montantBrut;

        return $this;
    }

    public function getCotisationSocial(): ?float
    {
        return $this->cotisationSocial;
    }

    public function setCotisationSocial(?float $cotisationSocial): static
    {
        $this->cotisationSocial = $cotisationSocial;

        return $this;
    }

    public function getIrsa(): ?float
    {
        return $this->irsa;
    }

    public function setIrsa(?float $irsa): static
    {
        $this->irsa = $irsa;

        return $this;
    }

    public function getMontantNet(): ?float
    {
        return $this->montantNet;
    }

    public function setMontantNet(?float $montantNet): static
    {
        $this->montantNet = $montantNet;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        // unset the owning side of the relation if necessary
        if ($employe === null && $this->employe !== null) {
            $this->employe->setSalaire(null);
        }

        // set the owning side of the relation if necessary
        if ($employe !== null && $employe->getSalaire() !== $this) {
            $employe->setSalaire($this);
        }

        $this->employe = $employe;

        return $this;
    }
}
