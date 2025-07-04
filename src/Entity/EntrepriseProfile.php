<?php

namespace App\Entity;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\BusinessModel\Subcription;
use App\Entity\Finance\Devise;
use App\Entity\Entreprise\Favoris;
use App\Entity\Moderateur\Metting;
use App\Entity\Entreprise\JobListing;
use App\Repository\EntrepriseProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: EntrepriseProfileRepository::class)]
#[Vich\Uploadable]
class EntrepriseProfile
{
    const SIZE_SMALL = 'SM';
    const SIZE_MEDIUM = 'MD';
    const SIZE_LARGE = 'LG';

    const STATUS_VALID = 'VALID';
    const STATUS_PENDING = 'PENDING';
    const STATUS_FULL_PREMIUM = 'FULL_PREMIUM';
    const STATUS_PREMIUM = 'PREMIUM';
    const STATUS_BANNED = 'BANNED';

    const CHOICE_SIZE = [        
         'Petite (1-10 employés)' => self::SIZE_SMALL ,
         'Moyenne (11-100 employés)' => self::SIZE_MEDIUM ,
         'Grande (plus de 100 employés)' => self::SIZE_LARGE ,
    ];

    const CHOICE_STATUS = [        
        'Valide' => self::STATUS_VALID,
        'En attente' => self::STATUS_PENDING,
        'Premium' => self::STATUS_PREMIUM,
        'Banni' => self::STATUS_BANNED,
    ];  

    public static function getTailles() {
        return [
            self::SIZE_SMALL =>        'Petite' ,
            self::SIZE_MEDIUM =>        'Moyenne' ,
            self::SIZE_LARGE =>        'Grande' ,
        ];
    }

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Validée' => self::STATUS_VALID ,
            'Accès Illimité' => self::STATUS_FULL_PREMIUM ,
            'Premium' => self::STATUS_PREMIUM ,
            'Banni' => self::STATUS_BANNED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>        '<span class="badge bg-warning">En attente</span>' ,  
            self::STATUS_VALID =>      '<span class="badge bg-success">Validée</span>' ,  
            self::STATUS_PREMIUM =>      '<span class="badge bg-dark">Premium</span>' ,  
            self::STATUS_FULL_PREMIUM =>      '<span class="badge bg-info">Accès Illimité</span>' ,  
            self::STATUS_BANNED =>       '<span class="badge bg-danger">Banni</span>' ,
        ];
    }
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'entrepriseProfile', cascade: ['persist', 'remove'])]
    private ?User $entreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: JobListing::class, cascade: ['remove'])]
    private Collection $jobListings;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Metting::class, cascade: ['remove'])]
    private Collection $mettings;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Secteur::class, inversedBy: 'entreprise')]
    private Collection $secteurs;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Favoris::class)]
    private Collection $favoris;

    #[ORM\ManyToOne(inversedBy: 'entrepriseProfiles')]
    private ?Devise $devise = null;

    #[ORM\OneToMany(mappedBy: 'entrepriseProfile', targetEntity: Prestation::class)]
    private Collection $prestations;

    #[ORM\ManyToOne(inversedBy: 'entrepriseProfile', cascade: ['persist', 'remove'])]
    private ?BoostVisibility $boostVisibility = null;

    #[ORM\ManyToOne(inversedBy: 'entrepriseProfiles', cascade: ['persist', 'remove'])]
    private ?Boost $boost = null;

    #[Vich\UploadableField(mapping: 'logo_company', fileNameProperty: 'fileName')]
    private ?File $file = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'entrepriseProfiles')]
    private ?BoostFacebook $boostFacebook = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPremium = null;

    /**
     * @var Collection<int, Subcription>
     */
    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Subcription::class)]
    private Collection $subcriptions;

    public function __construct()
    {
        $this->jobListings = new ArrayCollection();
        $this->mettings = new ArrayCollection();
        $this->secteurs = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->subcriptions = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getNom();
    }
    
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->createdAt,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->createdAt = $data['createdAt'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?User
    {
        return $this->entreprise;
    }

    public function setEntreprise(?User $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;

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
            $jobListing->setEntreprise($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            // set the owning side to null (unless already changed)
            if ($jobListing->getEntreprise() === $this) {
                $jobListing->setEntreprise(null);
            }
        }

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
            $metting->setEntreprise($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getEntreprise() === $this) {
                $metting->setEntreprise(null);
            }
        }

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
     * @return Collection<int, Secteur>
     */
    public function getSecteurs(): Collection
    {
        return $this->secteurs;
    }

    public function addSecteur(Secteur $secteur): static
    {
        if (!$this->secteurs->contains($secteur)) {
            $this->secteurs->add($secteur);
            $secteur->addEntreprise($this);
        }

        return $this;
    }

    public function removeSecteur(Secteur $secteur): static
    {
        if ($this->secteurs->removeElement($secteur)) {
            $secteur->removeEntreprise($this);
        }

        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
    
    /**
     * Récupère toutes les candidatures pour les annonces d'emploi de cette entreprise.
     * @return array
     */
    public function getAllApplications() {
        $allApplications = [];
        foreach ($this->jobListings as $jobListing) {
            foreach ($jobListing->getApplications() as $application) {
                $allApplications[] = $application;
            }
        }

        // Trie les candidatures par date de création décroissante.
        usort($allApplications, function($a, $b) {
            // Remplacez 'getCreatedAt' par la méthode ou la propriété appropriée de votre objet 'application'.
            return $b->getDateCandidature() <=> $a->getDateCandidature();
        });

        return $allApplications;
    }

    /**
     * @return Collection<int, Favoris>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(Favoris $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setEntreprise($this);
        }

        return $this;
    }

    public function removeFavori(Favoris $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            // set the owning side to null (unless already changed)
            if ($favori->getEntreprise() === $this) {
                $favori->setEntreprise(null);
            }
        }

        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

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
            $prestation->setEntrepriseProfile($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getEntrepriseProfile() === $this) {
                $prestation->setEntrepriseProfile(null);
            }
        }

        return $this;
    }

    public function getBoostVisibility(): ?BoostVisibility
    {
        return $this->boostVisibility;
    }

    public function setBoostVisibility(?BoostVisibility $boostVisibility): static
    {
        $this->boostVisibility = $boostVisibility;

        return $this;
    }

    public function getBoost(): ?Boost
    {
        return $this->boost;
    }

    public function setBoost(?Boost $boost): static
    {
        $this->boost = $boost;

        return $this;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();
        }

    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBoostFacebook(): ?BoostFacebook
    {
        return $this->boostFacebook;
    }

    public function setBoostFacebook(?BoostFacebook $boostFacebook): static
    {
        $this->boostFacebook = $boostFacebook;

        return $this;
    }

    public function isIsPremium(): ?bool
    {
        return $this->isPremium;
    }

    public function setIsPremium(?bool $isPremium): static
    {
        $this->isPremium = $isPremium;

        return $this;
    }

    /**
     * @return Collection<int, Subcription>
     */
    public function getSubcriptions(): Collection
    {
        return $this->subcriptions;
    }

    public function addSubcription(Subcription $subcription): static
    {
        if (!$this->subcriptions->contains($subcription)) {
            $this->subcriptions->add($subcription);
            $subcription->setEntreprise($this);
        }

        return $this;
    }

    public function removeSubcription(Subcription $subcription): static
    {
        if ($this->subcriptions->removeElement($subcription)) {
            // set the owning side to null (unless already changed)
            if ($subcription->getEntreprise() === $this) {
                $subcription->setEntreprise(null);
            }
        }

        return $this;
    }
}
