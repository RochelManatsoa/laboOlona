<?php

namespace App\Entity\Candidate;

use Doctrine\DBAL\Types\Types;
use App\Entity\CandidateProfile;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Candidate\ExperiencesRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ExperiencesRepository::class)]
class Experiences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['identity'])]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['identity'])]
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[Groups(['identity'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'experiences')]
    private ?CandidateProfile $profil = null;

    #[Groups(['identity'])]
    #[ORM\Column(length: 255)]
    private ?string $entreprise = null;

    #[ORM\Column]
    #[Groups(['identity'])]
    private ?bool $enPoste = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['identity'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['identity'])]
    private ?\DateTimeInterface $dateFin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getProfil(): ?CandidateProfile
    {
        return $this->profil;
    }

    public function setProfil(?CandidateProfile $profil): static
    {
        $this->profil = $profil;

        return $this;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }

    public function setEntreprise(string $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function isEnPoste(): ?bool
    {
        return $this->enPoste;
    }

    public function setEnPoste(bool $enPoste): static
    {
        $this->enPoste = $enPoste;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }
}
