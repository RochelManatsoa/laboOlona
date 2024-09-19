<?php

namespace App\Entity\Finance;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use App\Entity\Finance\Salaire;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\Finance\EmployeRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
class Employe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEmbauche = null;

    #[ORM\Column]
    private ?int $nombreEnfants = null;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Contrat::class)]
    private Collection $contrats;

    #[ORM\OneToOne(inversedBy: 'employe', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $matricule = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cnaps = null;

    #[ORM\Column(nullable: true)]
    private ?bool $sexe = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fonction = null;

    #[ORM\OneToOne(inversedBy: 'employe', cascade: ['persist', 'remove'])]
    private ?Salaire $salaire = null;

    #[ORM\Column]
    private ?float $salaireBase = null;

    #[ORM\Column(nullable: true)]
    private ?int $congePris = null;

    #[ORM\OneToOne(mappedBy: 'employe', cascade: ['persist', 'remove'])]
    private ?Avantage $avantage = null;

    #[ORM\OneToMany(mappedBy: 'employe', targetEntity: Simulateur::class, cascade: ['persist','remove'])]
    private Collection $simulateurs;

    public function __construct()
    {
        $this->contrats = new ArrayCollection();
        $this->simulateurs = new ArrayCollection();
    }
    
    public function getSimulateurCount(): int
    {
        return count($this->simulateurs);
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(?\DateTimeInterface $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;

        return $this;
    }

    public function getNombreEnfants(): ?int
    {
        return $this->nombreEnfants;
    }

    public function setNombreEnfants(int $nombreEnfants): static
    {
        $this->nombreEnfants = $nombreEnfants;

        return $this;
    }

    /**
     * @return Collection<int, Contrat>
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): static
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats->add($contrat);
            $contrat->setEmploye($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): static
    {
        if ($this->contrats->removeElement($contrat)) {
            // set the owning side to null (unless already changed)
            if ($contrat->getEmploye() === $this) {
                $contrat->setEmploye(null);
            }
        }

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

    public function getMatricule(): ?int
    {
        return $this->matricule;
    }

    public function setMatricule(?int $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getCnaps(): ?string
    {
        return $this->cnaps;
    }

    public function setCnaps(?string $cnaps): static
    {
        $this->cnaps = $cnaps;

        return $this;
    }

    public function isSexe(): ?bool
    {
        return $this->sexe;
    }

    public function setSexe(?bool $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(?string $cin): static
    {
        $this->cin = $cin;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getSalaire(): ?Salaire
    {
        return $this->salaire;
    }

    public function setSalaire(?Salaire $salaire): static
    {
        $this->salaire = $salaire;

        return $this;
    }

    public function getSalaireBase(): ?float
    {
        return $this->salaireBase;
    }

    public function setSalaireBase(float $salaireBase): static
    {
        $this->salaireBase = $salaireBase;

        return $this;
    }

    public function getCongePris(): ?int
    {
        return $this->congePris;
    }

    public function setCongePris(?int $congePris): static
    {
        $this->congePris = $congePris;

        return $this;
    }

    public function getAvantage(): ?Avantage
    {
        return $this->avantage;
    }

    public function setAvantage(?Avantage $avantage): static
    {
        // unset the owning side of the relation if necessary
        if ($avantage === null && $this->avantage !== null) {
            $this->avantage->setEmploye(null);
        }

        // set the owning side of the relation if necessary
        if ($avantage !== null && $avantage->getEmploye() !== $this) {
            $avantage->setEmploye($this);
        }

        $this->avantage = $avantage;

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
            $simulateur->setEmploye($this);
        }

        return $this;
    }

    public function removeSimulateur(Simulateur $simulateur): static
    {
        if ($this->simulateurs->removeElement($simulateur)) {
            // set the owning side to null (unless already changed)
            if ($simulateur->getEmploye() === $this) {
                $simulateur->setEmploye(null);
            }
        }

        return $this;
    }
}
