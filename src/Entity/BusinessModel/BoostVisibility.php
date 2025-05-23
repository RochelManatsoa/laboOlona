<?php

namespace App\Entity\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use App\Entity\User;
use App\Repository\BusinessModel\BoostVisibilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoostVisibilityRepository::class)]
class BoostVisibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationDays = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: CandidateProfile::class)]
    private Collection $candidateProfile;

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: EntrepriseProfile::class)]
    private Collection $entrepriseProfile;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities')]
    private ?Boost $boost = null;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities')]
    private ?BoostFacebook $boostFacebook = null;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities', cascade: ['persist', 'remove'])]
    private ?Prestation $prestation = null;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities', cascade: ['persist', 'remove'])]
    private ?JobListing $jobListing = null;

    public function __construct()
    {
        $this->candidateProfile = new ArrayCollection();
        $this->entrepriseProfile = new ArrayCollection();
    }

    public function isExpired(): bool
    {
        $now = new \DateTime();
        return $this->endDate !== null && $now > $this->endDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDurationDays(): ?int
    {
        return $this->durationDays;
    }

    public function setDurationDays(?int $durationDays): static
    {
        $this->durationDays = $durationDays;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getCandidateProfile(): Collection
    {
        return $this->candidateProfile;
    }

    public function addCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if (!$this->candidateProfile->contains($candidateProfile)) {
            $this->candidateProfile->add($candidateProfile);
            $candidateProfile->setBoostVisibility($this);
        }

        return $this;
    }

    public function removeCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if ($this->candidateProfile->removeElement($candidateProfile)) {
            // set the owning side to null (unless already changed)
            if ($candidateProfile->getBoostVisibility() === $this) {
                $candidateProfile->setBoostVisibility(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EntrepriseProfile>
     */
    public function getEntrepriseProfile(): Collection
    {
        return $this->entrepriseProfile;
    }

    public function addEntrepriseProfile(EntrepriseProfile $entrepriseProfile): static
    {
        if (!$this->entrepriseProfile->contains($entrepriseProfile)) {
            $this->entrepriseProfile->add($entrepriseProfile);
            $entrepriseProfile->setBoostVisibility($this);
        }

        return $this;
    }

    public function removeEntrepriseProfile(EntrepriseProfile $entrepriseProfile): static
    {
        if ($this->entrepriseProfile->removeElement($entrepriseProfile)) {
            // set the owning side to null (unless already changed)
            if ($entrepriseProfile->getBoostVisibility() === $this) {
                $entrepriseProfile->setBoostVisibility(null);
            }
        }

        return $this;
    }

    public function getBoost(): ?Boost
    {
        return $this->boost;
    }

    public function setBoost(?Boost $boost): static
    {
        $this->boost = $boost;

        return $this;
    }

    public function getBoostFacebook(): ?BoostFacebook
    {
        return $this->boostFacebook;
    }

    public function setBoostFacebook(?BoostFacebook $boostFacebook): static
    {
        $this->boostFacebook = $boostFacebook;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPrestation(): ?Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(?Prestation $prestation): static
    {
        $this->prestation = $prestation;

        return $this;
    }

    public function getJobListing(): ?JobListing
    {
        return $this->jobListing;
    }

    public function setJobListing(?JobListing $jobListing): static
    {
        $this->jobListing = $jobListing;

        return $this;
    }
}
