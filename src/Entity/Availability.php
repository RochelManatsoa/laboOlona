<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
class Availability
{

    const TYPE_IMMEDIATE = 'immediate';
    const TYPE_FROM_DATE = 'from-date';
    const TYPE_FULL_TIME = 'full-time';
    const TYPE_PART_TIME = 'part-time';

    const CHOICE_TYPE = [
        'Immédiatement' => self::TYPE_IMMEDIATE,
        'A partir du' => self::TYPE_FROM_DATE,
        'Temps plein' => self::TYPE_FULL_TIME,
        'Temps partiel' => self::TYPE_PART_TIME,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: 'availability', targetEntity: CandidateProfile::class)]
    private Collection $candidats;

    #[ORM\OneToMany(mappedBy: 'availability', targetEntity: Prestation::class)]
    private Collection $prestations;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
        $this->prestations = new ArrayCollection();
    }

    public function __toString(): string
    {
        $available = '';
        switch ($this->getNom()) {
            case self::TYPE_IMMEDIATE :
                $available = 'Disponible';
                break;
            
            case self::TYPE_FROM_DATE :
                $available = 'A partir du '. $this->getDateDebut() ;
                break;

            case self::TYPE_FULL_TIME :
                $available = 'Temps plein';
                break;

            case self::TYPE_PART_TIME :
                $available = 'Temps partiel';
                break;
        }
        
        return $available;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

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

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getCandidats(): Collection
    {
        return $this->candidats;
    }

    public function addCandidat(CandidateProfile $candidat): static
    {
        if (!$this->candidats->contains($candidat)) {
            $this->candidats->add($candidat);
            $candidat->setAvailability($this);
        }

        return $this;
    }

    public function removeCandidat(CandidateProfile $candidat): static
    {
        if ($this->candidats->removeElement($candidat)) {
            // set the owning side to null (unless already changed)
            if ($candidat->getAvailability() === $this) {
                $candidat->setAvailability(null);
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
            $prestation->setAvailability($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getAvailability() === $this) {
                $prestation->setAvailability(null);
            }
        }

        return $this;
    }
}
