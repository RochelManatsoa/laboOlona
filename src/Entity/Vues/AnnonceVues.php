<?php

namespace App\Entity\Vues;

use App\Entity\Entreprise\JobListing;
use App\Repository\Vues\AnnonceVuesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnonceVuesRepository::class)]
class AnnonceVues
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idAdress = null;

    #[ORM\ManyToOne(inversedBy: 'annonceVues')]
    private ?JobListing $annonce = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAdress(): ?string
    {
        return $this->idAdress;
    }

    public function setIdAdress(string $idAdress): static
    {
        $this->idAdress = $idAdress;

        return $this;
    }

    public function getAnnonce(): ?JobListing
    {
        return $this->annonce;
    }

    public function setAnnonce(?JobListing $annonce): static
    {
        $this->annonce = $annonce;

        return $this;
    }
}
