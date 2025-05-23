<?php

namespace App\Entity\Moderateur;

use App\Entity\CandidateProfile;
use App\Entity\Candidate\CV;
use App\Repository\Moderateur\EditedCvRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditedCvRepository::class)]
class EditedCv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cvLink = null;

    #[ORM\ManyToOne(inversedBy: 'editedCvs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CandidateProfile $candidat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $safeFileName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\OneToOne(mappedBy: 'edited', cascade: ['persist'])]
    private ?CV $cV = null;

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

    public function setSafeFileName(?string $safeFileName): static
    {
        $this->safeFileName = $safeFileName;

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

    public function getCV(): ?CV
    {
        return $this->cV;
    }

    public function setCV(?CV $cV): static
    {
        // unset the owning side of the relation if necessary
        if ($cV === null && $this->cV !== null) {
            $this->cV->setEdited(null);
        }

        // set the owning side of the relation if necessary
        if ($cV !== null && $cV->getEdited() !== $this) {
            $cV->setEdited($this);
        }

        $this->cV = $cV;

        return $this;
    }
}
