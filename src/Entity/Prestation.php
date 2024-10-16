<?php

namespace App\Entity;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\Prestation\TypePrestation;
use App\Entity\Vues\PrestationVues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Candidate\Competences;
use App\Repository\PrestationRepository;
use App\Entity\Prestation\TarifPrestation;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PrestationRepository::class)]
#[Vich\Uploadable]
class Prestation
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_VALID = 'VALID';
    const STATUS_FEATURED = 'FEATURED';
    const STATUS_DELETED = 'DELETED';
    const STATUS_SUSPENDED = 'SUSPENDED';
    const STATUS_COMPLETED = 'COMPLETED';

    const MODALITE_SITE = 'SITE';
    const MODALITE_DISTANCE = 'DISTANCE';
    const MODALITE_HYBRIDE = 'HYBRIDE';

    const CHOICE_STATUS = [        
        'En attente' => self::STATUS_PENDING,
        'Validée' => self::STATUS_VALID,
        'Boostée' => self::STATUS_FEATURED,
        'Effacée' => self::STATUS_DELETED,
        'Suspendue' => self::STATUS_SUSPENDED,
        'Complète' => self::STATUS_COMPLETED,
    ];

    const CHOICE_MODALITE = [        
        'Sur site' => self::MODALITE_SITE,
        'A distance' => self::MODALITE_DISTANCE,
        'Hybride' => self::MODALITE_HYBRIDE,
    ];

    public static function getArrayStatuses() {
        return [
             self::STATUS_PENDING ,
             self::STATUS_VALID ,
             self::STATUS_FEATURED,
             self::STATUS_DELETED,
             self::STATUS_SUSPENDED ,
             self::STATUS_COMPLETED ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $competencesRequises = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $tarifsProposes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modalitesPrestation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $specialisations = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $medias = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $evaluations = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?EntrepriseProfile $entrepriseProfile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $disponibilites = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cleanDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $openai = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isGenerated = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?Secteur $secteurs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motsCles = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeService = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $portfolioLinks = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $temoignages = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactTelephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contactReseauxSociaux = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preferencesCommunication = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsParticulieres = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $engagementQualite = null;

    #[ORM\Column(nullable: true)]
    private ?bool $termesConditionsAccepted = null;

    #[ORM\OneToOne(mappedBy: 'prestation', cascade: ['persist', 'remove'])]
    private ?TarifPrestation $tarifPrestation = null;

    #[ORM\ManyToOne(inversedBy: 'prestations', cascade: ['persist', 'remove'])]
    private ?Availability $availability = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, inversedBy: 'prestations')]
    private Collection $competences;

    #[Vich\UploadableField(mapping: 'medias_prestation', fileNameProperty: 'fileName')]
    private ?File $file = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'prestation')]
    private ?BoostVisibility $boostVisibility = null;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?Boost $boost = null;

    #[ORM\ManyToOne(inversedBy: 'prestation')]
    private ?TypePrestation $typePrestation = null;

    #[ORM\OneToMany(mappedBy: 'prestation', targetEntity: PrestationVues::class)]
    private Collection $prestationVues;

    #[ORM\ManyToOne(inversedBy: 'prestations')]
    private ?BoostFacebook $boostFacebook = null;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->prestationVues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCompetencesRequises(): ?array
    {
        return $this->competencesRequises;
    }

    public function setCompetencesRequises(?array $competencesRequises): static
    {
        $this->competencesRequises = $competencesRequises;

        return $this;
    }

    public function getModalitesPrestation(): ?string
    {
        return $this->modalitesPrestation;
    }

    public function setModalitesPrestation(?string $modalitesPrestation): static
    {
        $this->modalitesPrestation = $modalitesPrestation;

        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        $this->candidateProfile = $candidateProfile;

        return $this;
    }

    public function getEntrepriseProfile(): ?EntrepriseProfile
    {
        return $this->entrepriseProfile;
    }

    public function setEntrepriseProfile(?EntrepriseProfile $entrepriseProfile): static
    {
        $this->entrepriseProfile = $entrepriseProfile;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCleanDescription(): ?string
    {
        return $this->cleanDescription;
    }

    public function setCleanDescription(?string $cleanDescription): static
    {
        $this->cleanDescription = $cleanDescription;

        return $this;
    }

    public function getOpenai(): ?string
    {
        return $this->openai;
    }

    public function setOpenai(?string $openai): static
    {
        $this->openai = $openai;

        return $this;
    }

    public function isIsGenerated(): ?bool
    {
        return $this->isGenerated;
    }

    public function setIsGenerated(?bool $isGenerated): static
    {
        $this->isGenerated = $isGenerated;

        return $this;
    }

    public function getSecteurs(): ?Secteur
    {
        return $this->secteurs;
    }

    public function setSecteurs(?Secteur $secteurs): static
    {
        $this->secteurs = $secteurs;

        return $this;
    }

    public function getMotsCles(): ?string
    {
        return $this->motsCles;
    }

    public function setMotsCles(?string $motsCles): static
    {
        $this->motsCles = $motsCles;

        return $this;
    }

    public function getTypeService(): ?string
    {
        return $this->typeService;
    }

    public function setTypeService(?string $typeService): static
    {
        $this->typeService = $typeService;

        return $this;
    }

    public function getPortfolioLinks(): ?string
    {
        return $this->portfolioLinks;
    }

    public function setPortfolioLinks(?string $portfolioLinks): static
    {
        $this->portfolioLinks = $portfolioLinks;

        return $this;
    }

    public function getTemoignages(): ?string
    {
        return $this->temoignages;
    }

    public function setTemoignages(?string $temoignages): static
    {
        $this->temoignages = $temoignages;

        return $this;
    }

    public function getContactTelephone(): ?string
    {
        return $this->contactTelephone;
    }

    public function setContactTelephone(?string $contactTelephone): static
    {
        $this->contactTelephone = $contactTelephone;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactReseauxSociaux(): ?string
    {
        return $this->contactReseauxSociaux;
    }

    public function setContactReseauxSociaux(?string $contactReseauxSociaux): static
    {
        $this->contactReseauxSociaux = $contactReseauxSociaux;

        return $this;
    }

    public function getPreferencesCommunication(): ?string
    {
        return $this->preferencesCommunication;
    }

    public function setPreferencesCommunication(?string $preferencesCommunication): static
    {
        $this->preferencesCommunication = $preferencesCommunication;

        return $this;
    }

    public function getConditionsParticulieres(): ?string
    {
        return $this->conditionsParticulieres;
    }

    public function setConditionsParticulieres(?string $conditionsParticulieres): static
    {
        $this->conditionsParticulieres = $conditionsParticulieres;

        return $this;
    }

    public function getEngagementQualite(): ?string
    {
        return $this->engagementQualite;
    }

    public function setEngagementQualite(?string $engagementQualite): static
    {
        $this->engagementQualite = $engagementQualite;

        return $this;
    }

    public function isTermesConditionsAccepted(): ?bool
    {
        return $this->termesConditionsAccepted;
    }

    public function setTermesConditionsAccepted(?bool $termesConditionsAccepted): static
    {
        $this->termesConditionsAccepted = $termesConditionsAccepted;

        return $this;
    }

    public function getTarifsProposes(): ?string
    {
        return $this->tarifsProposes;
    }

    public function setTarifsProposes(?string $tarifsProposes): static
    {
        $this->tarifsProposes = $tarifsProposes;

        return $this;
    }

    public function getSpecialisations(): ?string
    {
        return $this->specialisations;
    }

    public function setSpecialisations(?string $specialisations): static
    {
        $this->specialisations = $specialisations;

        return $this;
    }

    public function getMedias(): ?string
    {
        return $this->medias;
    }

    public function setMedias(?string $medias): static
    {
        $this->medias = $medias;

        return $this;
    }

    public function getEvaluations(): ?string
    {
        return $this->evaluations;
    }

    public function setEvaluations(?string $evaluations): static
    {
        $this->evaluations = $evaluations;

        return $this;
    }

    public function getDisponibilites(): ?string
    {
        return $this->disponibilites;
    }

    public function setDisponibilites(?string $disponibilites): static
    {
        $this->disponibilites = $disponibilites;

        return $this;
    }

    public function getTarifPrestation(): ?TarifPrestation
    {
        return $this->tarifPrestation;
    }

    public function setTarifPrestation(?TarifPrestation $tarifPrestation): static
    {
        // unset the owning side of the relation if necessary
        if ($tarifPrestation === null && $this->tarifPrestation !== null) {
            $this->tarifPrestation->setPrestation(null);
        }

        // set the owning side of the relation if necessary
        if ($tarifPrestation !== null && $tarifPrestation->getPrestation() !== $this) {
            $tarifPrestation->setPrestation($this);
        }

        $this->tarifPrestation = $tarifPrestation;

        return $this;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): static
    {
        $this->availability = $availability;

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

    public function getTypePrestation(): ?TypePrestation
    {
        return $this->typePrestation;
    }

    public function setTypePrestation(?TypePrestation $typePrestation): static
    {
        $this->typePrestation = $typePrestation;

        return $this;
    }

    /**
     * @return Collection<int, PrestationVues>
     */
    public function getPrestationVues(): Collection
    {
        return $this->prestationVues;
    }

    public function addPrestationVue(PrestationVues $prestationVue): static
    {
        if (!$this->prestationVues->contains($prestationVue)) {
            $this->prestationVues->add($prestationVue);
            $prestationVue->setPrestation($this);
        }

        return $this;
    }

    public function removePrestationVue(PrestationVues $prestationVue): static
    {
        if ($this->prestationVues->removeElement($prestationVue)) {
            // set the owning side to null (unless already changed)
            if ($prestationVue->getPrestation() === $this) {
                $prestationVue->setPrestation(null);
            }
        }

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
}
