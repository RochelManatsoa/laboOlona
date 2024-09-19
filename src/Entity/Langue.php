<?php

namespace App\Entity;

use App\Entity\Candidate\Langages;
use App\Entity\Entreprise\JobListing;
use App\Repository\LangueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LangueRepository::class)]
class Langue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'langue', targetEntity: Langages::class)]
    private Collection $langages;

    #[ORM\ManyToMany(targetEntity: JobListing::class, mappedBy: 'langues')]
    private Collection $jobListings;

    public function __construct()
    {
        $this->langages = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, Langages>
     */
    public function getLangages(): Collection
    {
        return $this->langages;
    }

    public function addLangage(Langages $langage): static
    {
        if (!$this->langages->contains($langage)) {
            $this->langages->add($langage);
            $langage->setLangue($this);
        }

        return $this;
    }

    public function removeLangage(Langages $langage): static
    {
        if ($this->langages->removeElement($langage)) {
            // set the owning side to null (unless already changed)
            if ($langage->getLangue() === $this) {
                $langage->setLangue(null);
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
            $jobListing->addLangue($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            $jobListing->removeLangue($this);
        }

        return $this;
    }
}
