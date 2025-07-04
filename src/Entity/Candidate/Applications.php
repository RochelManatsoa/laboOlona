<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\Moderateur\Assignation;
use App\Repository\Candidate\ApplicationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationsRepository::class)]
class Applications
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_ARCHIVED = 'ARCHIVED';
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_METTING = 'METTING';


    public static function getStatuses() {
        return [
            'En cours' => self::STATUS_PENDING ,
            'Non retenues' => self::STATUS_REJECTED ,
            'Archivée' => self::STATUS_ARCHIVED ,
            'Acceptée' => self::STATUS_ACCEPTED ,
            'Rendez-vous' => self::STATUS_METTING ,
        ];
    }

    public static function getLabels() {
        return [
             self::STATUS_PENDING => '<span class="badge bg-primary">En attente</span>',
             self::STATUS_REJECTED => '<span class="badge bg-danger">Non retenues</span>',
             self::STATUS_ARCHIVED => '<span class="badge bg-secondary">Archivée</span>',
             self::STATUS_ACCEPTED => '<span class="badge bg-success">Acceptée</span>',
             self::STATUS_METTING => '<span class="badge bg-warning">Rendez-vous</span>',
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_PENDING ,
             self::STATUS_REJECTED ,
             self::STATUS_ARCHIVED ,
             self::STATUS_ACCEPTED ,
             self::STATUS_METTING ,
        ];
    }
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    private ?CandidateProfile $candidat = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    private ?JobListing $annonce = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lettreMotivation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvLink = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCandidature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $pretentionSalariale = null;

    #[ORM\OneToOne(mappedBy: 'application', cascade: ['persist', 'remove'])]
    private ?Assignation $assignation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?CandidateProfile
    {
        return $this->candidat;
    }

    public function setCandidat(?CandidateProfile $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getAnnonce(): ?JobListing
    {
        return $this->annonce;
    }

    public function setAnnonce(?JobListing $annonce): static
    {
        $this->annonce = $annonce;

        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(?string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;

        return $this;
    }

    public function getCvLink(): ?string
    {
        return $this->cvLink;
    }

    public function setCvLink(?string $cvLink): static
    {
        $this->cvLink = $cvLink;

        return $this;
    }

    public function getDateCandidature(): ?\DateTimeInterface
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(\DateTimeInterface $dateCandidature): static
    {
        $this->dateCandidature = $dateCandidature;

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

    public function getPretentionSalariale(): ?string
    {
        return $this->pretentionSalariale;
    }

    public function setPretentionSalariale(?string $pretentionSalariale): static
    {
        $this->pretentionSalariale = $pretentionSalariale;

        return $this;
    }
    
    /**
    * Vérifie si cette application a été soumise par un candidat spécifique.
    *
    * @param CandidateProfile $candidateProfile Le profil de candidat à vérifier.
    * @return bool Retourne true si l'application a été soumise par le candidat, false sinon.
    */
    public function isApplyByCandidate(CandidateProfile $candidateProfile): bool
    {
        // Vérifie si le candidat associé à cette application correspond au candidat fourni
        return $this->candidat && $this->candidat->getId() === $candidateProfile->getId();
    }

    public function getAssignation(): ?Assignation
    {
        return $this->assignation;
    }

    public function setAssignation(?Assignation $assignation): static
    {
        // unset the owning side of the relation if necessary
        if ($assignation === null && $this->assignation !== null) {
            $this->assignation->setApplication(null);
        }

        // set the owning side of the relation if necessary
        if ($assignation !== null && $assignation->getApplication() !== $this) {
            $assignation->setApplication($this);
        }

        $this->assignation = $assignation;

        return $this;
    }
}
