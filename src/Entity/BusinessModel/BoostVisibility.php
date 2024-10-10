<?php

namespace App\Entity\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
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

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: Prestation::class, cascade: ['remove', 'persist'])]
    private Collection $prestation;

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: CandidateProfile::class)]
    private Collection $candidateProfile;

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: EntrepriseProfile::class)]
    private Collection $entrepriseProfile;

    #[ORM\OneToMany(mappedBy: 'boostVisibility', targetEntity: JobListing::class, cascade: ['remove', 'persist'])]
    private Collection $jobListing;

    #[ORM\ManyToOne(inversedBy: 'boostVisibilities')]
    private ?Boost $boost = null;

    public function __construct()
    {
        $this->prestation = new ArrayCollection();
        $this->candidateProfile = new ArrayCollection();
        $this->entrepriseProfile = new ArrayCollection();
        $this->jobListing = new ArrayCollection();
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
     * @return Collection<int, Prestation>
     */
    public function getPrestation(): Collection
    {
        return $this->prestation;
    }

    public function addPrestation(Prestation $prestation): static
    {
        if (!$this->prestation->contains($prestation)) {
            $this->prestation->add($prestation);
            $prestation->setBoostVisibility($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestation->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getBoostVisibility() === $this) {
                $prestation->setBoostVisibility(null);
            }
        }

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

    /**
     * @return Collection<int, JobListing>
     */
    public function getJobListing(): Collection
    {
        return $this->jobListing;
    }

    public function addJobListing(JobListing $jobListing): static
    {
        if (!$this->jobListing->contains($jobListing)) {
            $this->jobListing->add($jobListing);
            $jobListing->setBoostVisibility($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListing->removeElement($jobListing)) {
            // set the owning side to null (unless already changed)
            if ($jobListing->getBoostVisibility() === $this) {
                $jobListing->setBoostVisibility(null);
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
}
