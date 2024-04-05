<?php

namespace App\Entity\Finance;

use App\Repository\Finance\ContratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
class Contrat
{
    const TYPE_FREELANCE = 'FREELANCE';
    const TYPE_EMPLOYE = 'EMPLOYE';

    const STATUS_PENDING = 'PENDING';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_VALID = 'VALID';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_ARCHIVED = 'ARCHIVED';
    const STATUS_SUSPENDED = 'SUSPENDED';
    const STATUS_RENEWED = 'RENEWED';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_UNFULFILLED = 'UNFULFILLED';

    public static function getTypeContrat() {
        return [
            'Freelance' => self::TYPE_FREELANCE ,
            'Employé' => self::TYPE_EMPLOYE ,
        ];
    }

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Simulation OK' => self::STATUS_VALID ,
            'Actif' => self::STATUS_ACTIVE ,
            'Expiré' => self::STATUS_EXPIRED ,
            'Resilié' => self::STATUS_ARCHIVED ,
            'Suspendu' => self::STATUS_SUSPENDED ,
            'Renouvelé' => self::STATUS_RENEWED ,
            'Approuvé' => self::STATUS_APPROVED ,
            'Non exécuté' => self::STATUS_UNFULFILLED ,
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_PENDING ,
             self::STATUS_ACTIVE ,
             self::STATUS_EXPIRED ,
             self::STATUS_ARCHIVED ,
             self::STATUS_SUSPENDED ,
             self::STATUS_RENEWED ,
             self::STATUS_APPROVED ,
             self::STATUS_UNFULFILLED ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contrats')]
    private ?Employe $employe = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private ?float $salaireBase = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Simulateur $simulateur = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getSalaireBase(): ?float
    {
        return $this->salaireBase;
    }

    public function setSalaireBase(float $salaireBase): static
    {
        $this->salaireBase = $salaireBase;

        return $this;
    }

    public function getSimulateur(): ?Simulateur
    {
        return $this->simulateur;
    }

    public function setSimulateur(?Simulateur $simulateur): static
    {
        $this->simulateur = $simulateur;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
}
