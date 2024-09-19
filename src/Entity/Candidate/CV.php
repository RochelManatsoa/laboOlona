<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Moderateur\EditedCv;
use App\Repository\Candidate\CVRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CVRepository::class)]
class CV
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cvLink = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    private ?CandidateProfile $candidat = null;

    #[ORM\Column(length: 255)]
    private ?string $safeFileName = null;

    #[ORM\OneToOne(inversedBy: 'cV', cascade: ['persist', 'remove'])]
    private ?EditedCv $edited = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCvLink(): ?string
    {
        return $this->cvLink;
    }

    public function setCvLink(string $cvLink): static
    {
        $this->cvLink = $cvLink;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

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

    public function getSafeFileName(): ?string
    {
        return $this->safeFileName;
    }

    public function setSafeFileName(string $safeFileName): static
    {
        $this->safeFileName = $safeFileName;

        return $this;
    }

    public function getEdited(): ?EditedCv
    {
        return $this->edited;
    }

    public function setEdited(?EditedCv $edited): static
    {
        $this->edited = $edited;

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
}
