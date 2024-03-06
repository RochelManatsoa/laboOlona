<?php

namespace App\Entity\Moderateur;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Enum\StatusMetting;
use App\Entity\ModerateurProfile;
use App\Repository\Moderateur\MettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MettingRepository::class)]
class Metting
{

    const STATUS_PENDING = 'PENDING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_RESCHEDULED = 'RESCHEDULED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_NOSHOW = 'NOSHOW';


    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Confirmé' => self::STATUS_CONFIRMED ,
            'Reprogrammé' => self::STATUS_RESCHEDULED ,
            'Annulé' => self::STATUS_CANCELLED ,
            'Complété' => self::STATUS_COMPLETED ,
            'Non présentation' => self::STATUS_NOSHOW ,
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_PENDING ,
             self::STATUS_CONFIRMED ,
             self::STATUS_RESCHEDULED ,
             self::STATUS_CANCELLED ,
             self::STATUS_COMPLETED ,
             self::STATUS_NOSHOW ,
        ];
    }


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?EntrepriseProfile $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?CandidateProfile $candidat = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?ModerateurProfile $moderateur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateRendezVous = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $customId = null;

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

    public function getCandidat(): ?CandidateProfile
    {
        return $this->candidat;
    }

    public function setCandidat(?CandidateProfile $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getModerateur(): ?ModerateurProfile
    {
        return $this->moderateur;
    }

    public function setModerateur(?ModerateurProfile $moderateur): static
    {
        $this->moderateur = $moderateur;

        return $this;
    }

    public function getDateRendezVous(): ?\DateTimeInterface
    {
        return $this->dateRendezVous;
    }

    public function setDateRendezVous(\DateTimeInterface $dateRendezVous): static
    {
        $this->dateRendezVous = $dateRendezVous;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

        return $this;
    }

    public function getCustomId(): ?Uuid
    {
        return $this->customId;
    }

    public function setCustomId(?Uuid $customId): static
    {
        $this->customId = $customId;

        return $this;
    }
}
