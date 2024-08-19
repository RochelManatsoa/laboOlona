<?php

namespace App\Entity\BusinessModel;

use App\Entity\ModerateurProfile;
use App\Repository\BusinessModel\BoostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoostRepository::class)]
class Boost
{
    const TYPE_PROFILE_CANDIDATE = 'PROFILE_CANDIDATE';
    const TYPE_PROFILE_RECRUITER = 'PROFILE_RECRUITER';
    const TYPE_PRESTATION_CANDIDATE = 'PRESTATION_CANDIDATE';
    const TYPE_PRESTATION_RECRUITER = 'PRESTATION_RECRUITER';
    const TYPE_JOB_LISTING = 'JOB_LISTING';

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'boosts')]
    private ?ModerateurProfile $moderator = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationDays = null;

    #[ORM\OneToMany(mappedBy: 'boost', targetEntity: BoostVisibility::class)]
    private Collection $boostVisibilities;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    public function __construct()
    {
        $this->boostVisibilities = new ArrayCollection();
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getModerator(): ?ModerateurProfile
    {
        return $this->moderator;
    }

    public function setModerator(?ModerateurProfile $moderator): static
    {
        $this->moderator = $moderator;

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
            $boostVisibility->setBoost($this);
        }

        return $this;
    }

    public function removeBoostVisibility(BoostVisibility $boostVisibility): static
    {
        if ($this->boostVisibilities->removeElement($boostVisibility)) {
            // set the owning side to null (unless already changed)
            if ($boostVisibility->getBoost() === $this) {
                $boostVisibility->setBoost(null);
            }
        }

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
