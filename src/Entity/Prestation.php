<?php

namespace App\Entity;

use App\Repository\PrestationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrestationRepository::class)]
class Prestation
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_VALID = 'VALID';
    const STATUS_FEATURED = 'FEATURED';
    const STATUS_DELETED = 'DELETED';
    const STATUS_SUSPENDED = 'SUSPENDED';
    const STATUS_COMPLETED = 'COMPLETED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $competencesRequises = null;

    #[ORM\Column(nullable: true)]
    private ?array $tarifsProposes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modalitesPrestation = null;

    #[ORM\Column(nullable: true)]
    private ?array $specialisations = null;

    #[ORM\Column(nullable: true)]
    private ?array $medias = null;

    #[ORM\Column(nullable: true)]
    private ?array $evaluations = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?EntrepriseProfile $entrepriseProfile = null;

    #[ORM\Column(nullable: true)]
    private ?array $disponibilites = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cleanDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $openai = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isGenerated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getCompetencesRequises(): ?array
    {
        return $this->competencesRequises;
    }

    public function setCompetencesRequises(?array $competencesRequises): static
    {
        $this->competencesRequises = $competencesRequises;

        return $this;
    }

    public function getTarifsProposes(): ?array
    {
        return $this->tarifsProposes;
    }

    public function setTarifsProposes(?array $tarifsProposes): static
    {
        $this->tarifsProposes = $tarifsProposes;

        return $this;
    }

    public function getModalitesPrestation(): ?string
    {
        return $this->modalitesPrestation;
    }

    public function setModalitesPrestation(?string $modalitesPrestation): static
    {
        $this->modalitesPrestation = $modalitesPrestation;

        return $this;
    }

    public function getSpecialisations(): ?array
    {
        return $this->specialisations;
    }

    public function setSpecialisations(?array $specialisations): static
    {
        $this->specialisations = $specialisations;

        return $this;
    }

    public function getMedias(): ?array
    {
        return $this->medias;
    }

    public function setMedias(?array $medias): static
    {
        $this->medias = $medias;

        return $this;
    }

    public function getEvaluations(): ?array
    {
        return $this->evaluations;
    }

    public function setEvaluations(?array $evaluations): static
    {
        $this->evaluations = $evaluations;

        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        $this->candidateProfile = $candidateProfile;

        return $this;
    }

    public function getEntrepriseProfile(): ?EntrepriseProfile
    {
        return $this->entrepriseProfile;
    }

    public function setEntrepriseProfile(?EntrepriseProfile $entrepriseProfile): static
    {
        $this->entrepriseProfile = $entrepriseProfile;

        return $this;
    }

    public function getDisponibilites(): ?array
    {
        return $this->disponibilites;
    }

    public function setDisponibilites(?array $disponibilites): static
    {
        $this->disponibilites = $disponibilites;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

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

    public function getCleanDescription(): ?string
    {
        return $this->cleanDescription;
    }

    public function setCleanDescription(?string $cleanDescription): static
    {
        $this->cleanDescription = $cleanDescription;

        return $this;
    }

    public function getOpenai(): ?string
    {
        return $this->openai;
    }

    public function setOpenai(?string $openai): static
    {
        $this->openai = $openai;

        return $this;
    }

    public function isIsGenerated(): ?bool
    {
        return $this->isGenerated;
    }

    public function setIsGenerated(?bool $isGenerated): static
    {
        $this->isGenerated = $isGenerated;

        return $this;
    }
}
