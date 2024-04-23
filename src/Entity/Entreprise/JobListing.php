<?php

namespace App\Entity\Entreprise;

use App\Entity\Candidate\Applications;
use App\Entity\Candidate\Competences;
use App\Entity\EntrepriseProfile;
use App\Entity\Langue;
use App\Entity\Moderateur\Assignation;
use App\Entity\Moderateur\TypeContrat;
use App\Entity\Referrer\Referral;
use App\Entity\Secteur;
use App\Entity\Vues\AnnonceVues;
use App\Repository\Entreprise\JobListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: JobListingRepository::class)]
class JobListing
{
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_ARCHIVED = 'ARCHIVED';
    const STATUS_UNPUBLISHED = 'UNPUBLISHED';
    const STATUS_DELETED = 'DELETED';
    const STATUS_FEATURED = 'FEATURED';
    const STATUS_RESERVED = 'RESERVED';

    public static function getCompanyStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Bruillon' => self::STATUS_DRAFT ,
            'Publiée' => self::STATUS_PUBLISHED ,
            'Rejetée' => self::STATUS_REJECTED ,
            'Archivée' => self::STATUS_ARCHIVED ,
            'Mis en avant' => self::STATUS_FEATURED ,
        ];
    }

    public static function getStatuses() {
        return [
            'Bruillon' => self::STATUS_DRAFT ,
            'Publiée' => self::STATUS_PUBLISHED ,
            'En attente' => self::STATUS_PENDING ,
            'Rejetée' => self::STATUS_REJECTED ,
            'Expirée' => self::STATUS_EXPIRED ,
            'Archivée' => self::STATUS_ARCHIVED ,
            'Non publiée' => self::STATUS_UNPUBLISHED ,
            'Effacée' => self::STATUS_DELETED ,
            'Mis en avant' => self::STATUS_FEATURED ,
            'Réservée' => self::STATUS_RESERVED ,
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_DRAFT ,
             self::STATUS_PUBLISHED ,
             self::STATUS_PENDING ,
             self::STATUS_REJECTED ,
             self::STATUS_EXPIRED ,
             self::STATUS_ARCHIVED ,
             self::STATUS_UNPUBLISHED ,
             self::STATUS_DELETED ,
             self::STATUS_FEATURED ,
             self::STATUS_RESERVED ,
        ];
    }
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobListings')]
    private ?EntrepriseProfile $entreprise = null;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $salaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Applications::class, cascade: ['remove'])]
    private Collection $applications;

    #[ORM\ManyToOne(inversedBy: 'jobListings')]
    private ?Secteur $secteur = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, inversedBy: 'jobListings')]
    private Collection $competences;

    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: AnnonceVues::class, cascade: ['remove'])]
    private Collection $annonceVues;

    #[ORM\ManyToMany(targetEntity: Langue::class, inversedBy: 'jobListings')]
    private Collection $langues;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $jobId = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombrePoste = null;

    #[ORM\ManyToOne(inversedBy: 'jobListings')]
    private ?TypeContrat $typeContrat = null;

    #[ORM\OneToMany(mappedBy: 'jobListing', targetEntity: Assignation::class, cascade: ['remove'])]
    private Collection $assignations;

    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Referral::class)]
    private Collection $referrals;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: true)]
    private ?string $prime = null;

    #[ORM\ManyToOne(inversedBy: 'annonce', cascade: ['persist'])]
    private ?BudgetAnnonce $budgetAnnonce = null;

    #[ORM\OneToOne(mappedBy: 'annonce', cascade: ['persist', 'remove'])]
    private ?PrimeAnnonce $primeAnnonce = null;

    public function __toString()
    {
        return $this->titre;        
    }

    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->competences = new ArrayCollection();
        $this->annonceVues = new ArrayCollection();
        $this->langues = new ArrayCollection();
        $this->assignations = new ArrayCollection();
        $this->referrals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?EntrepriseProfile
    {
        return $this->entreprise;
    }

    public function setEntreprise(?EntrepriseProfile $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSalaire(): ?string
    {
        return $this->salaire;
    }

    public function setSalaire(string $salaire): static
    {
        $this->salaire = $salaire;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * @return Collection<int, Applications>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Applications $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setAnnonce($this);
        }

        return $this;
    }

    public function removeApplication(Applications $application): static
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getAnnonce() === $this) {
                $application->setAnnonce(null);
            }
        }

        return $this;
    }

    public function getSecteur(): ?Secteur
    {
        return $this->secteur;
    }

    public function setSecteur(?Secteur $secteur): static
    {
        $this->secteur = $secteur;

        return $this;
    }

    /**
     * @return Collection<int, Competences>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competences $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
        }

        return $this;
    }

    public function removeCompetence(Competences $competence): static
    {
        $this->competences->removeElement($competence);

        return $this;
    }

    /**
     * @return Collection<int, AnnonceVues>
     */
    public function getAnnonceVues(): Collection
    {
        return $this->annonceVues;
    }

    public function addAnnonceVue(AnnonceVues $annonceVue): static
    {
        if (!$this->annonceVues->contains($annonceVue)) {
            $this->annonceVues->add($annonceVue);
            $annonceVue->setAnnonce($this);
        }

        return $this;
    }

    public function removeAnnonceVue(AnnonceVues $annonceVue): static
    {
        if ($this->annonceVues->removeElement($annonceVue)) {
            // set the owning side to null (unless already changed)
            if ($annonceVue->getAnnonce() === $this) {
                $annonceVue->setAnnonce(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Langue>
     */
    public function getLangues(): Collection
    {
        return $this->langues;
    }

    public function addLangue(Langue $langue): static
    {
        if (!$this->langues->contains($langue)) {
            $this->langues->add($langue);
        }

        return $this;
    }

    public function removeLangue(Langue $langue): static
    {
        $this->langues->removeElement($langue);

        return $this;
    }

    public function getJobId(): ?Uuid
    {
        return $this->jobId;
    }

    public function setJobId(Uuid $jobId): static
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function getNombrePoste(): ?int
    {
        return $this->nombrePoste;
    }

    public function setNombrePoste(?int $nombrePoste): static
    {
        $this->nombrePoste = $nombrePoste;

        return $this;
    }

    public function getTypeContrat(): ?TypeContrat
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?TypeContrat $typeContrat): static
    {
        $this->typeContrat = $typeContrat;

        return $this;
    }

    /**
     * @return Collection<int, Assignation>
     */
    public function getAssignations(): Collection
    {
        return $this->assignations;
    }

    public function addAssignation(Assignation $assignation): static
    {
        if (!$this->assignations->contains($assignation)) {
            $this->assignations->add($assignation);
            $assignation->setJobListing($this);
        }

        return $this;
    }

    public function removeAssignation(Assignation $assignation): static
    {
        if ($this->assignations->removeElement($assignation)) {
            // set the owning side to null (unless already changed)
            if ($assignation->getJobListing() === $this) {
                $assignation->setJobListing(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Referral>
     */
    public function getReferrals(): Collection
    {
        return $this->referrals;
    }

    public function addReferral(Referral $referral): static
    {
        if (!$this->referrals->contains($referral)) {
            $this->referrals->add($referral);
            $referral->setAnnonce($this);
        }

        return $this;
    }

    public function removeReferral(Referral $referral): static
    {
        if ($this->referrals->removeElement($referral)) {
            // set the owning side to null (unless already changed)
            if ($referral->getAnnonce() === $this) {
                $referral->setAnnonce(null);
            }
        }

        return $this;
    }

    public function getPrime(): ?string
    {
        return $this->prime;
    }

    public function setPrime(?string $prime): static
    {
        $this->prime = $prime;

        return $this;
    }

    public function getBudgetAnnonce(): ?BudgetAnnonce
    {
        return $this->budgetAnnonce;
    }

    public function setBudgetAnnonce(?BudgetAnnonce $budgetAnnonce): static
    {
        $this->budgetAnnonce = $budgetAnnonce;

        return $this;
    }

    public function getPrimeAnnonce(): ?PrimeAnnonce
    {
        return $this->primeAnnonce;
    }

    public function setPrimeAnnonce(?PrimeAnnonce $primeAnnonce): static
    {
        // unset the owning side of the relation if necessary
        if ($primeAnnonce === null && $this->primeAnnonce !== null) {
            $this->primeAnnonce->setAnnonce(null);
        }

        // set the owning side of the relation if necessary
        if ($primeAnnonce !== null && $primeAnnonce->getAnnonce() !== $this) {
            $primeAnnonce->setAnnonce($this);
        }

        $this->primeAnnonce = $primeAnnonce;

        return $this;
    }
}
