<?php

namespace App\Entity\Finance;

use App\Repository\Finance\DeviseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DeviseRepository::class)]
class Devise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['devise'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['devise'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['devise'])]
    private ?string $symbole = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['devise'])]
    private ?float $taux = null;

    #[ORM\OneToMany(mappedBy: 'devise', targetEntity: Simulateur::class)]
    private Collection $simulateurs;

    #[ORM\Column(length: 255)]
    #[Groups(['devise'])]
    private ?string $slug = null;

    public function __toString()
    {
        return $this->nom .' ( '.$this->symbole.' )' ;
    }

    public function __construct()
    {
        $this->simulateurs = new ArrayCollection();
    }

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

    public function getSymbole(): ?string
    {
        return $this->symbole;
    }

    public function setSymbole(?string $symbole): static
    {
        $this->symbole = $symbole;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(?float $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    /**
     * @return Collection<int, Simulateur>
     */
    public function getSimulateurs(): Collection
    {
        return $this->simulateurs;
    }

    public function addSimulateur(Simulateur $simulateur): static
    {
        if (!$this->simulateurs->contains($simulateur)) {
            $this->simulateurs->add($simulateur);
            $simulateur->setDevise($this);
        }

        return $this;
    }

    public function removeSimulateur(Simulateur $simulateur): static
    {
        if ($this->simulateurs->removeElement($simulateur)) {
            // set the owning side to null (unless already changed)
            if ($simulateur->getDevise() === $this) {
                $simulateur->setDevise(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
