<?php

namespace App\Entity;

use App\Entity\Candidate\CV;
use App\Entity\Candidate\Langages;
use App\Entity\Candidate\Social;
use App\Entity\Candidate\TarifCandidat;
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['identity'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?User $candidat = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, mappedBy: 'profil', cascade: ['persist', 'remove'])]
    private Collection $competences;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Experiences::class, cascade: ['persist', 'remove'])]
    private Collection $experiences;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Applications::class, cascade: ['persist', 'remove'])]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Metting::class, cascade: ['persist', 'remove'])]
    private Collection $mettings;

    #[ORM\ManyToMany(targetEntity: Secteur::class,  inversedBy: 'canditat')]
    private Collection $secteurs;

    #[Vich\UploadableField(mapping: 'cv_expert', fileNameProperty: 'fileName')]
    private ?File $file = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['identity'])]
    private ?string $titre = null;

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
    private ?string $status = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $uid = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CV::class, cascade: ['persist', 'remove'])]
    private Collection $cvs;

    #[ORM\Column(nullable: true)]
    private ?bool $emailSent = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: EditedCv::class, orphanRemoval: true)]
    private Collection $editedCvs;

    #[ORM\ManyToOne(inversedBy: 'candidats', cascade: ['persist', 'remove'])]
    private ?Availability $availability = null;

    #[ORM\OneToOne(mappedBy: 'candidat', cascade: ['persist', 'remove'])]
    private ?TarifCandidat $tarifCandidat = null;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Assignation::class, cascade: ['persist', 'remove'])]
    private Collection $assignations;

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
    }

    public function __toString()
    {
        return $this->getCandidat()->getNom();
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
}
