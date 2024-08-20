<?php

namespace App\Entity\Entreprise;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Repository\Entreprise\FavorisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
class Favoris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favoris')]
    private ?EntrepriseProfile $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'favoris')]
    private ?CandidateProfile $candidat = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?EntrepriseProfile
    {
        return $this->entreprise;
    }

    public function setEntreprise(?EntrepriseProfile $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getCandidat(): ?CandidateProfile
    {
        return $this->candidat;
    }

    public function setCandidat(?CandidateProfile $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
