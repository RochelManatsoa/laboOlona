<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Langue;
use App\Repository\Candidate\LangagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LangagesRepository::class)]
class Langages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(nullable: true)]
    private ?int $niveau = null;

    #[ORM\ManyToOne(inversedBy: 'langages')]
    private ?CandidateProfile $profile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'langages')]
    private ?Langue $langue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(?int $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getProfile(): ?CandidateProfile
    {
        return $this->profile;
    }

    public function setProfile(?CandidateProfile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLangue(): ?Langue
    {
        return $this->langue;
    }

    public function setLangue(?Langue $langue): static
    {
        $this->langue = $langue;

        return $this;
    }
}
