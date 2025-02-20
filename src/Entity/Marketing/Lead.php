<?php

namespace App\Entity\Marketing;

use App\Entity\User;
use App\Repository\Marketing\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: '`lead`')]
class Lead
{

    const STATUS_NEW = 'NEW';
    const STATUS_NRP = 'NRP';
    const STATUS_REP = 'REP';
    const STATUS_PI = 'PI';
    const STATUS_ARAP = 'ARAP';
    const STATUS_MAIL_OK = 'MAIL_OK';
    const STATUS_MAIL_TO_SEND = 'MAIL_TO_SEND';
    const STATUS_RDV = 'RDV'; 

    public static function getStatuses() {
        return [
            'Nouveau' => self::STATUS_NEW ,
            'Ne répond pas' => self::STATUS_NRP ,
            'Répondeur' => self::STATUS_REP ,
            'Pas intéressé' => self::STATUS_PI ,
            'À rappeler' => self::STATUS_ARAP ,
            'Mail envoyé' => self::STATUS_MAIL_OK ,
            'Mail à envoyer' => self::STATUS_MAIL_TO_SEND ,
            'Rendez-vous' => self::STATUS_RDV ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_NEW =>          '<span class="badge bg-info">Nouveau</span>' ,  
            self::STATUS_NRP =>          '<span class="badge bg-info">Ne répond pas</span>' ,  
            self::STATUS_REP =>        '<span class="badge bg-warning">Répondeur</span>' ,  
            self::STATUS_PI =>      '<span class="badge bg-success">Pas intéressé</span>' ,  
            self::STATUS_ARAP =>      '<span class="badge bg-danger">À rappeler</span>' ,  
            self::STATUS_MAIL_OK =>      '<span class="badge bg-danger">Mail envoyé</span>' ,  
            self::STATUS_MAIL_TO_SEND =>       '<span class="badge bg-dark">Mail à envoyer</span>' ,
            self::STATUS_RDV =>       '<span class="badge bg-dark">Rendez-vous</span>' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'leads')]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastContacted = null;

    #[ORM\ManyToOne(inversedBy: 'leads')]
    private ?Source $source = null;

    #[ORM\Column(nullable: true)]
    private ?int $emailSent = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_NEW;
        $this->emailSent = 0;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function getLastContacted(): ?\DateTimeInterface
    {
        return $this->lastContacted;
    }

    public function setLastContacted(?\DateTimeInterface $lastContacted): static
    {
        $this->lastContacted = $lastContacted;

        return $this;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource(?Source $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getEmailSent(): ?int
    {
        return $this->emailSent;
    }

    public function setEmailSent(?int $emailSent): static
    {
        $this->emailSent = $emailSent;

        return $this;
    }
}
