<?php

namespace App\Entity;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\Candidate\CV;
use App\Entity\Candidate\Langages;
use App\Entity\Candidate\Social;
use App\Entity\Candidate\TarifCandidat;
use App\Entity\Entreprise\Favoris;
use App\Entity\Moderateur\Assignation;
use App\Entity\Moderateur\EditedCv;
use App\Entity\Vues\CandidatVues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Moderateur\Metting;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use App\Entity\Candidate\Applications;
use Doctrine\Common\Collections\Collection;
use App\Repository\CandidateProfileRepository;
use DateTime;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CandidateProfileRepository::class)]
#[Vich\Uploadable]
class CandidateProfile
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_BANNISHED = 'BANNISHED';
    const STATUS_VALID = 'VALID';
    const STATUS_FEATURED = 'FEATURED';
    const STATUS_RESERVED = 'RESERVED';

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Bani' => self::STATUS_BANNISHED ,
            'Valide' => self::STATUS_VALID ,
            'Mis en avant' => self::STATUS_FEATURED ,
            'Vivier' => self::STATUS_RESERVED ,
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_PENDING ,
             self::STATUS_BANNISHED ,
             self::STATUS_VALID ,
             self::STATUS_FEATURED ,
             self::STATUS_RESERVED ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['identity', 'open_ai'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?User $candidat = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['identity'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['identity', 'open_ai'])]
    private ?string $resume = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, mappedBy: 'profil', cascade: ['persist', 'remove'])]
    private Collection $competences;

    #[Groups(['open_ai'])]
    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Experiences::class, cascade: ['persist', 'remove'])]
    private Collection $experiences;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Applications::class, cascade: ['persist', 'remove'])]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Metting::class, cascade: ['persist', 'remove'])]
    private Collection $mettings;

    #[ORM\ManyToMany(targetEntity: Secteur::class,  inversedBy: 'candidat')]
    #[Groups(['identity'])]
    private Collection $secteurs;

    #[Vich\UploadableField(mapping: 'cv_expert', fileNameProperty: 'fileName')]
    private ?File $file = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['identity'])]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['identity'])]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['identity'])]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['identity', 'open_ai'])]
    private ?string $titre = null;

    #[Groups(['open_ai'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Langages::class, cascade: ['persist', 'remove'])]
    private Collection $langages;

    #[ORM\OneToOne(mappedBy: 'candidat', cascade: ['persist', 'remove'])]
    private ?Social $social = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CandidatVues::class)]
    private Collection $vues;

    #[ORM\Column]
    private ?bool $isValid = null;

    #[ORM\Column(length: 255)]
    #[Groups(['identity', 'open_ai'])]
    private ?string $status = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['identity'])]
    private ?Uuid $uid = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CV::class, cascade: ['persist', 'remove'])]
    private Collection $cvs;

    #[ORM\Column(nullable: true)]
    private ?bool $emailSent = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: EditedCv::class, orphanRemoval: true)]
    private Collection $editedCvs;

    #[ORM\ManyToOne(inversedBy: 'candidats', cascade: ['persist', 'remove'])]
    #[Groups(['identity'])]
    private ?Availability $availability = null;

    #[ORM\OneToOne(mappedBy: 'candidat', cascade: ['persist', 'remove'])]
    private ?TarifCandidat $tarifCandidat = null;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Assignation::class, cascade: ['persist', 'remove'])]
    private Collection $assignations;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Favoris::class)]
    private Collection $favoris;

    #[ORM\Column]
    private ?int $relanceCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $relancedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tesseractResult = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultFree = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultPremium = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $traductionEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $badKeywords = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tools = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $technologies = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resumeCandidat = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isGeneretated = null;

    #[ORM\OneToMany(mappedBy: 'candidateProfile', targetEntity: Prestation::class)]
    private Collection $prestations;

    #[Groups(['boost'])]
    #[ORM\ManyToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?BoostVisibility $boostVisibility = null;

    #[Groups(['boost'])]
    #[ORM\ManyToOne(inversedBy: 'candidateProfiles', cascade: ['persist', 'remove'])]
    private ?Boost $boost = null;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->experiences = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->mettings = new ArrayCollection();
        $this->secteurs = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->langages = new ArrayCollection();
        $this->vues = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->editedCvs = new ArrayCollection();
        $this->assignations = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->uid = new Uuid(Uuid::v4());
        $this->isValid = false;
        $this->status = self::STATUS_PENDING;
    }

    public function __toString()
    {
        return $this->getCandidat()->getPrenom();
    }
    
    public function __serialize(): array
    {
        // Retournez ici les propriétés à sérialiser
        return [
            'id' => $this->id,
            'createdAt' => $this->createdAt,
            // Ajoutez d'autres propriétés si nécessaire
            // Notez que certaines propriétés, comme les objets et les collections d'entités, ne doivent pas être sérialisées
        ];
    }

    public function __unserialize(array $data): void
    {
        // Restaurez l'état de l'objet à partir des données sérialisées
        $this->id = $data['id'] ?? null;
        $this->createdAt = $data['createdAt'] ?? null;
        // Restaurez d'autres propriétés si elles étaient sérialisées
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?User
    {
        return $this->candidat;
    }

    public function setCandidat(?User $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

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
            $competence->addProfil($this);
        }

        return $this;
    }

    public function removeCompetence(Competences $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            $competence->removeProfil($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Experiences>
     */
    public function getExperiences(): Collection
    {
        return $this->experiences;
    }

    public function addExperience(Experiences $experience): static
    {
        if (!$this->experiences->contains($experience)) {
            $this->experiences->add($experience);
            $experience->setProfil($this);
        }

        return $this;
    }

    public function removeExperience(Experiences $experience): static
    {
        if ($this->experiences->removeElement($experience)) {
            // set the owning side to null (unless already changed)
            if ($experience->getProfil() === $this) {
                $experience->setProfil(null);
            }
        }

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
            $application->setCandidat($this);
        }

        return $this;
    }

    public function removeApplication(Applications $application): static
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getCandidat() === $this) {
                $application->setCandidat(null);
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
            $metting->setCandidat($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getCandidat() === $this) {
                $metting->setCandidat(null);
            }
        }

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
            $secteur->addCandidat($this);
        }

        return $this;
    }

    public function removeSecteur(Secteur $secteur): static
    {
        if ($this->secteurs->removeElement($secteur)) {
            $secteur->removeCandidat($this);
        }

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
    
    public function serialize()
    {
        $this->fileName = base64_encode($this->fileName);
    }

    public function unserialize($serialized)
    {
        $this->fileName = base64_decode($this->fileName);

    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;

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
            $langage->setProfile($this);
        }

        return $this;
    }

    public function removeLangage(Langages $langage): static
    {
        if ($this->langages->removeElement($langage)) {
            // set the owning side to null (unless already changed)
            if ($langage->getProfile() === $this) {
                $langage->setProfile(null);
            }
        }

        return $this;
    }

    public function getSocial(): ?Social
    {
        return $this->social;
    }

    public function setSocial(?Social $social): static
    {
        // unset the owning side of the relation if necessary
        if ($social === null && $this->social !== null) {
            $this->social->setCandidat(null);
        }

        // set the owning side of the relation if necessary
        if ($social !== null && $social->getCandidat() !== $this) {
            $social->setCandidat($this);
        }

        $this->social = $social;

        return $this;
    }

    /**
     * @return Collection<int, CandidatVues>
     */
    public function getVues(): Collection
    {
        return $this->vues;
    }

    public function addVue(CandidatVues $vue): static
    {
        if (!$this->vues->contains($vue)) {
            $this->vues->add($vue);
            $vue->setCandidat($this);
        }

        return $this;
    }

    public function removeVue(CandidatVues $vue): static
    {
        if ($this->vues->removeElement($vue)) {
            // set the owning side to null (unless already changed)
            if ($vue->getCandidat() === $this) {
                $vue->setCandidat(null);
            }
        }

        return $this;
    }

    public function isIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUid(): ?Uuid
    {
        return $this->uid;
    }

    public function setUid(Uuid $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return Collection<int, CV>
     */
    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    public function addCv(CV $cv): static
    {
        if (!$this->cvs->contains($cv)) {
            $this->cvs->add($cv);
            $cv->setCandidat($this);
        }

        return $this;
    }

    public function removeCv(CV $cv): static
    {
        if ($this->cvs->removeElement($cv)) {
            // set the owning side to null (unless already changed)
            if ($cv->getCandidat() === $this) {
                $cv->setCandidat(null);
            }
        }

        return $this;
    }

    public function isEmailSent(): ?bool
    {
        return $this->emailSent;
    }

    public function setEmailSent(?bool $emailSent): static
    {
        $this->emailSent = $emailSent;

        return $this;
    }

    /**
     * @return Collection<int, EditedCv>
     */
    public function getEditedCvs(): Collection
    {
        return $this->editedCvs;
    }

    public function addEditedCv(EditedCv $editedCv): static
    {
        if (!$this->editedCvs->contains($editedCv)) {
            $this->editedCvs->add($editedCv);
            $editedCv->setCandidat($this);
        }

        return $this;
    }

    public function removeEditedCv(EditedCv $editedCv): static
    {
        if ($this->editedCvs->removeElement($editedCv)) {
            // set the owning side to null (unless already changed)
            if ($editedCv->getCandidat() === $this) {
                $editedCv->setCandidat(null);
            }
        }

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

    public function getTarifCandidat(): ?TarifCandidat
    {
        return $this->tarifCandidat;
    }

    public function setTarifCandidat(?TarifCandidat $tarifCandidat): static
    {
        // unset the owning side of the relation if necessary
        if ($tarifCandidat === null && $this->tarifCandidat !== null) {
            $this->tarifCandidat->setCandidat(null);
        }

        // set the owning side of the relation if necessary
        if ($tarifCandidat !== null && $tarifCandidat->getCandidat() !== $this) {
            $tarifCandidat->setCandidat($this);
        }

        $this->tarifCandidat = $tarifCandidat;

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
            $assignation->setProfil($this);
        }

        return $this;
    }

    public function removeAssignation(Assignation $assignation): static
    {
        if ($this->assignations->removeElement($assignation)) {
            // set the owning side to null (unless already changed)
            if ($assignation->getProfil() === $this) {
                $assignation->setProfil(null);
            }
        }

        return $this;
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
            $favori->setCandidat($this);
        }

        return $this;
    }

    public function removeFavori(Favoris $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            // set the owning side to null (unless already changed)
            if ($favori->getCandidat() === $this) {
                $favori->setCandidat(null);
            }
        }

        return $this;
    }

    #[Groups(['identity'])]
    public function getCountViews(): int
    {
        return $this->vues->count();
    }

    #[Groups(['identity'])]
    public function getCountApplications(): int
    {
        return $this->applications->count();
    }

    #[Groups(['identity'])]
    public function getCountExperiences(): int
    {
        return $this->experiences->count();
    }

    #[Groups(['identity'])]
    public function getCountCompetences(): int
    {
        return $this->competences->count();
    }

    #[Groups(['identity'])]
    public function getUrlImage(): string
    {
        return 'https://app.olona-talents.com/uploads/experts/'.$this->fileName;
    }

    #[Groups(['identity'])]
    public function getMatricule(): string
    {
        $letters = 'OT';
        $paddedId = sprintf('%04d', $this->getId());

        return $letters . $paddedId;
    }

    public function getRelanceCount(): ?int
    {
        return $this->relanceCount;
    }

    public function incrementRelanceCount(): self
    {
        $this->relanceCount++;
        $this->setRelancedAt(new DateTime());

        return $this;
    }

    public function setRelanceCount(int $relanceCount): static
    {
        $this->relanceCount = $relanceCount;

        return $this;
    }

    public function getRelancedAt(): ?\DateTimeInterface
    {
        return $this->relancedAt;
    }

    public function setRelancedAt(?\DateTimeInterface $relancedAt): static
    {
        $this->relancedAt = $relancedAt;

        return $this;
    }

    public function getTesseractResult(): ?string
    {
        return $this->tesseractResult;
    }

    public function setTesseractResult(?string $tesseractResult): static
    {
        $this->tesseractResult = $tesseractResult;

        return $this;
    }

    public function getResultFree(): ?string
    {
        return $this->resultFree;
    }

    public function setResultFree(?string $resultFree): static
    {
        $this->resultFree = $resultFree;

        return $this;
    }

    public function getResultPremium(): ?string
    {
        return $this->resultPremium;
    }

    public function setResultPremium(?string $resultPremium): static
    {
        $this->resultPremium = $resultPremium;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getTraductionEn(): ?string
    {
        return $this->traductionEn;
    }

    public function setTraductionEn(?string $traductionEn): static
    {
        $this->traductionEn = $traductionEn;

        return $this;
    }

    public function getBadKeywords(): ?string
    {
        return $this->badKeywords;
    }

    public function setBadKeywords(?string $badKeywords): static
    {
        $this->badKeywords = $badKeywords;

        return $this;
    }

    public function getTools(): ?string
    {
        return $this->tools;
    }

    public function setTools(?string $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    public function getTechnologies(): ?string
    {
        return $this->technologies;
    }

    public function setTechnologies(?string $technologies): static
    {
        $this->technologies = $technologies;

        return $this;
    }

    public function getResumeCandidat(): ?string
    {
        return $this->resumeCandidat;
    }

    public function setResumeCandidat(?string $resumeCandidat): static
    {
        $this->resumeCandidat = $resumeCandidat;

        return $this;
    }

    public function isIsGeneretated(): ?bool
    {
        return $this->isGeneretated;
    }

    public function setIsGeneretated(?bool $isGeneretated): static
    {
        $this->isGeneretated = $isGeneretated;

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
            $prestation->setCandidateProfile($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): static
    {
        if ($this->prestations->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getCandidateProfile() === $this) {
                $prestation->setCandidateProfile(null);
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
}
