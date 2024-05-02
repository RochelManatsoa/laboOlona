<?php

namespace App\Entity\Candidate;

use Doctrine\DBAL\Types\Types;
use App\Entity\CandidateProfile;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Entreprise\JobListing;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Candidate\CompetencesRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CompetencesRepository::class)]
class Competences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: CandidateProfile::class, inversedBy: 'competences')]
    private Collection $profil;

    #[ORM\Column(nullable: true)]
    private ?int $note = null;

    #[ORM\ManyToMany(targetEntity: JobListing::class, mappedBy: 'competences')]
    private Collection $jobListings;

    public function __construct()
    {
        $this->profil = new ArrayCollection();
        $this->jobListings = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->nom;
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
        $this->slug = $this->createSlug($nom); // Mettre à jour le slug lors du changement du nom
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
     * @return Collection<int, CandidateProfile>
     */
    public function getProfil(): Collection
    {
        return $this->profil;
    }

    public function addProfil(CandidateProfile $profil): static
    {
        if (!$this->profil->contains($profil)) {
            $this->profil->add($profil);
        }

        return $this;
    }

    public function removeProfil(CandidateProfile $profil): static
    {
        $this->profil->removeElement($profil);

        return $this;
    }

    private function createSlug(string $text): string
    {
        // Conversion du texte en slug (exemple simple)
        $slug = mb_strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugValue(): void
    {
        $this->slug = $this->createSlug($this->nom); // Mettre à jour le slug avant la persistance
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): static
    {
        $this->note = $note;

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
            $jobListing->addCompetence($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            $jobListing->removeCompetence($this);
        }

        return $this;
    }
}
