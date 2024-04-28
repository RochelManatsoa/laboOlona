<?php

namespace App\Entity;

use App\Entity\Entreprise\JobListing;
use App\Repository\SecteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SecteurRepository::class)]
class Secteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['identity'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: EntrepriseProfile::class, mappedBy: 'secteurs')]
    private Collection $entreprise;

    #[ORM\ManyToMany(targetEntity: CandidateProfile::class, mappedBy: 'secteurs')]
    private Collection $candidat;

    #[ORM\OneToMany(mappedBy: 'secteur', targetEntity: JobListing::class)]
    private Collection $jobListings;

    public function __toString()
    {
        return $this->nom;
    }

    public function __construct()
    {
        $this->entreprise = new ArrayCollection();
        $this->candidat = new ArrayCollection();
        $this->jobListings = new ArrayCollection();
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

    /**
     * @return Collection<int, EntrepriseProfile>
     */
    public function getEntreprise(): Collection
    {
        return $this->entreprise;
    }

    public function addEntreprise(EntrepriseProfile $entreprise): static
    {
        if (!$this->entreprise->contains($entreprise)) {
            $this->entreprise->add($entreprise);
        }

        return $this;
    }

    public function removeEntreprise(EntrepriseProfile $entreprise): static
    {
        $this->entreprise->removeElement($entreprise);

        return $this;
    }

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getCandidat(): Collection
    {
        return $this->candidat;
    }

    public function addCandidat(CandidateProfile $candidat): static
    {
        if (!$this->candidat->contains($candidat)) {
            $this->candidat->add($candidat);
        }

        return $this;
    }

    public function removeCandidat(CandidateProfile $candidat): static
    {
        $this->candidat->removeElement($candidat);

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
            $jobListing->setSecteur($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            // set the owning side to null (unless already changed)
            if ($jobListing->getSecteur() === $this) {
                $jobListing->setSecteur(null);
            }
        }

        return $this;
    }
}
