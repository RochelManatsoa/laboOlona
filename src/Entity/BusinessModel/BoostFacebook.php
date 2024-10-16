<?php

namespace App\Entity\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use App\Repository\BusinessModel\BoostFacebookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoostFacebookRepository::class)]
class BoostFacebook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $credit = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationDays = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'boostFacebook', targetEntity: CandidateProfile::class)]
    private Collection $candidateProfiles;

    #[ORM\OneToMany(mappedBy: 'boostFacebook', targetEntity: JobListing::class)]
    private Collection $jobListings;

    #[ORM\OneToMany(mappedBy: 'boostFacebook', targetEntity: Prestation::class)]
    private Collection $prestations;

    #[ORM\OneToMany(mappedBy: 'boostFacebook', targetEntity: EntrepriseProfile::class)]
    private Collection $entrepriseProfiles;

    #[ORM\OneToMany(mappedBy: 'boostFacebook', targetEntity: BoostVisibility::class)]
    private Collection $boostVisibilities;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->candidateProfiles = new ArrayCollection();
        $this->jobListings = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->boostVisibilities = new ArrayCollection();
        $this->entrepriseProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

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

    public function getCredit(): ?float
    {
        return $this->credit;
    }

    public function setCredit(?float $credit): static
    {
        $this->credit = $credit;

        return $this;
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
    public function getCandidateProfiles(): Collection
    {
        return $this->candidateProfiles;
    }

    public function addCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if (!$this->candidateProfiles->contains($candidateProfile)) {
            $this->candidateProfiles->add($candidateProfile);
            $candidateProfile->setBoostFacebook($this);
        }

        return $this;
    }

    public function removeCandidateProfile(CandidateProfile $candidateProfile): static
    {
        if ($this->candidateProfiles->removeElement($candidateProfile)) {
            // set the owning side to null (unless already changed)
            if ($candidateProfile->getBoostFacebook() === $this) {
                $candidateProfile->setBoostFacebook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JobListing>
     */
    public function getJobListings(): Collection
    {
        return $this->jobListings;
    }

    public function addJobListing(JobListing $jobListing): static
    {
        if (!$this->jobListings->contains($jobListing)) {
            $this->jobListings->add($jobListing);
            $jobListing->setBoostFacebook($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            // set the owning side to null (unless already changed)
            if ($jobListing->getBoostFacebook() === $this) {
                $jobListing->setBoostFacebook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Prestation>
     */
    public function getPrestations(): Collection
    {
        return $this->prestations;
    }

    public function addPrestation(Prestation $prestation): static
    {
        if (!$this->prestations->contains($prestation)) {
            $this->prestations->add($prestation);
            $prestation->setBoostFacebook($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getBoostFacebook() === $this) {
                $prestation->setBoostFacebook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EntrepriseProfile>
     */
    public function getEntrepriseProfiles(): Collection
    {
        return $this->entrepriseProfiles;
    }

    public function addEntrepriseProfile(EntrepriseProfile $entrepriseProfile): static
    {
        if (!$this->entrepriseProfiles->contains($entrepriseProfile)) {
            $this->entrepriseProfiles->add($entrepriseProfile);
            $entrepriseProfile->setBoostFacebook($this);
        }

        return $this;
    }

    public function removeEntrepriseProfile(EntrepriseProfile $entrepriseProfile): static
    {
        if ($this->entrepriseProfiles->removeElement($entrepriseProfile)) {
            // set the owning side to null (unless already changed)
            if ($entrepriseProfile->getBoostFacebook() === $this) {
                $entrepriseProfile->setBoostFacebook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BoostVisibility>
     */
    public function getBoostVisibilities(): Collection
    {
        return $this->boostVisibilities;
    }

    public function addBoostVisibility(BoostVisibility $boostVisibility): static
    {
        if (!$this->boostVisibilities->contains($boostVisibility)) {
            $this->boostVisibilities->add($boostVisibility);
            $boostVisibility->setBoostFacebook($this);
        }

        return $this;
    }

    public function removeBoostVisibility(BoostVisibility $boostVisibility): static
    {
        if ($this->boostVisibilities->removeElement($boostVisibility)) {
            // set the owning side to null (unless already changed)
            if ($boostVisibility->getBoostFacebook() === $this) {
                $boostVisibility->setBoostFacebook(null);
            }
        }

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
