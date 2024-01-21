<?php

namespace App\Entity\Moderateur;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Repository\Moderateur\AssignationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignationRepository::class)]
class Assignation
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_REFUSED = 'REFUSED';

    const TYPE_OLONA = 'OLONA';
    const TYPE_CANDIDAT = 'CANDIDAT';

    public static function getStatuses() {
        return [
            'En cours' => self::STATUS_PENDING ,
            'Acceptée' => self::STATUS_ACCEPTED ,
            'Refusée' => self::STATUS_REFUSED ,
        ];
    }
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAssignation = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinAssignation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rolePositionVisee = self::TYPE_OLONA;

    #[ORM\ManyToOne(inversedBy: 'assignations')]
    private ?CandidateProfile $profil = null;

    #[ORM\ManyToOne(inversedBy: 'assignations')]
    private ?JobListing $jobListing = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: true)]
    private ?string $forfait = null;

    public function __construct()
    {
        $this->dateAssignation = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAssignation(): ?\DateTimeInterface
    {
        return $this->dateAssignation;
    }

    public function setDateAssignation(\DateTimeInterface $dateAssignation): static
    {
        $this->dateAssignation = $dateAssignation;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getDateFinAssignation(): ?\DateTimeInterface
    {
        return $this->dateFinAssignation;
    }

    public function setDateFinAssignation(?\DateTimeInterface $dateFinAssignation): static
    {
        $this->dateFinAssignation = $dateFinAssignation;

        return $this;
    }

    public function getRolePositionVisee(): ?string
    {
        return $this->rolePositionVisee;
    }

    public function setRolePositionVisee(?string $rolePositionVisee): static
    {
        $this->rolePositionVisee = $rolePositionVisee;

        return $this;
    }

    public function getProfil(): ?CandidateProfile
    {
        return $this->profil;
    }

    public function setProfil(?CandidateProfile $profil): static
    {
        $this->profil = $profil;

        return $this;
    }

    public function getJobListing(): ?JobListing
    {
        return $this->jobListing;
    }

    public function setJobListing(?JobListing $jobListing): static
    {
        $this->jobListing = $jobListing;

        return $this;
    }

    public function getForfait(): ?string
    {
        return $this->forfait;
    }

    public function setForfait(?string $forfait): static
    {
        $this->forfait = $forfait;

        return $this;
    }
}
