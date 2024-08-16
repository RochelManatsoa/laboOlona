<?php

namespace App\Entity;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Package;
use App\Entity\Moderateur\Metting;
use App\Repository\ModerateurProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModerateurProfileRepository::class)]
class ModerateurProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'moderateurProfile', cascade: ['persist', 'remove'])]
    private ?User $moderateur = null;

    #[ORM\OneToMany(mappedBy: 'moderateur', targetEntity: Metting::class)]
    private Collection $mettings;

    #[ORM\OneToMany(mappedBy: 'moderator', targetEntity: Boost::class)]
    private Collection $boosts;

    #[ORM\OneToMany(mappedBy: 'moderator', targetEntity: Package::class)]
    private Collection $packages;

    public function __construct()
    {
        $this->mettings = new ArrayCollection();
        $this->boosts = new ArrayCollection();
        $this->packages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModerateur(): ?User
    {
        return $this->moderateur;
    }

    public function setModerateur(?User $moderateur): static
    {
        $this->moderateur = $moderateur;

        return $this;
    }

    /**
     * @return Collection<int, Metting>
     */
    public function getMettings(): Collection
    {
        return $this->mettings;
    }

    public function addMetting(Metting $metting): static
    {
        if (!$this->mettings->contains($metting)) {
            $this->mettings->add($metting);
            $metting->setModerateur($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getModerateur() === $this) {
                $metting->setModerateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Boost>
     */
    public function getBoosts(): Collection
    {
        return $this->boosts;
    }

    public function addBoost(Boost $boost): static
    {
        if (!$this->boosts->contains($boost)) {
            $this->boosts->add($boost);
            $boost->setModerator($this);
        }

        return $this;
    }

    public function removeBoost(Boost $boost): static
    {
        if ($this->boosts->removeElement($boost)) {
            // set the owning side to null (unless already changed)
            if ($boost->getModerator() === $this) {
                $boost->setModerator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Package>
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    public function addPackage(Package $package): static
    {
        if (!$this->packages->contains($package)) {
            $this->packages->add($package);
            $package->setModerator($this);
        }

        return $this;
    }

    public function removePackage(Package $package): static
    {
        if ($this->packages->removeElement($package)) {
            // set the owning side to null (unless already changed)
            if ($package->getModerator() === $this) {
                $package->setModerator(null);
            }
        }

        return $this;
    }
}
