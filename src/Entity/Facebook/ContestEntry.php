<?php

namespace App\Entity\Facebook;

use App\Entity\CandidateProfile;
use App\Entity\User;
use App\Repository\Facebook\ContestEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEntryRepository::class)]
class ContestEntry
{
    const STATUS_SEND = 'DRAFT';
    const STATUS_PENDING = 'PENDING';
    const STATUS_VALIDATED = 'VALIDATED';
    const STATUS_CV_EMPTY = 'NO_CV';
    const STATUS_INFOS_EMPTY = 'NO_CV';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_ARCHIVED = 'ARCHIVED';   

    public static function getStatuses() {
        return [
            'Soumis' => self::STATUS_SEND ,
            'En attente de validation' => self::STATUS_PENDING ,
            'Validée' => self::STATUS_VALIDATED ,
            'Informations manquantes' => self::STATUS_INFOS_EMPTY ,
            'CV manquant' => self::STATUS_CV_EMPTY ,
            'Publiée' => self::STATUS_PUBLISHED ,
            'Archivée' => self::STATUS_ARCHIVED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_SEND =>          '<span class="badge bg-info">Soumis</span>' ,  
            self::STATUS_PENDING =>        '<span class="badge bg-warning">En attente de validation</span>' ,  
            self::STATUS_VALIDATED =>      '<span class="badge bg-success">Validée</span>' ,  
            self::STATUS_CV_EMPTY =>      '<span class="badge bg-danger">CV manquant</span>' ,  
            self::STATUS_INFOS_EMPTY =>      '<span class="badge bg-danger">Informations manquantes</span>' ,  
            self::STATUS_PUBLISHED =>      '<span class="badge bg-success">Publié</span>' ,  
            self::STATUS_ARCHIVED =>       '<span class="badge bg-danger">Archivé</span>' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contestEntries')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'contestEntries')]
    private ?Contest $contest = null;

    #[ORM\Column]
    private ?bool $cvSumbitted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $submittedAt = null;

    #[ORM\ManyToOne(inversedBy: 'contestEntries')]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $step = null;

    #[ORM\Column(nullable: true)]
    private ?bool $emailSend = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    public function __construct()
    {
        $this->status = self::STATUS_SEND;
        $this->cvSumbitted = false;
        $this->step = 1;
        $this->submittedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContest(): ?Contest
    {
        return $this->contest;
    }

    public function setContest(?Contest $contest): static
    {
        $this->contest = $contest;

        return $this;
    }

    public function isCvSumbitted(): ?bool
    {
        return $this->cvSumbitted;
    }

    public function setCvSumbitted(bool $cvSumbitted): static
    {
        $this->cvSumbitted = $cvSumbitted;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeInterface $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function setStep(?int $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function isEmailSend(): ?bool
    {
        return $this->emailSend;
    }

    public function setEmailSend(?bool $emailSend): static
    {
        $this->emailSend = $emailSend;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }
}
