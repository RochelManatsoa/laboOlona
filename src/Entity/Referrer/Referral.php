<?php

namespace App\Entity\Referrer;

use App\Entity\Entreprise\JobListing;
use App\Entity\ReferrerProfile;
use App\Repository\Referrer\ReferralRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReferralRepository::class)]
class Referral
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_MODERATED = 'MODERATED';
    const STATUS_REFUSED = 'REFUSED';
    const STATUS_ARCHIVED = 'ARCHIVED';

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Accépté' => self::STATUS_ACCEPTED ,
            'Modéré' => self::STATUS_MODERATED ,
            'Refusé' => self::STATUS_REFUSED ,
            'Archivé' => self::STATUS_ARCHIVED ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $referredEmail = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'referrals')]
    private ?ReferrerProfile $referredBy = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $referralCode = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column]
    private ?int $step = null;

    #[ORM\ManyToOne(inversedBy: 'referrals')]
    private ?JobListing $annonce = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: true)]
    private ?string $rewards = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->referralCode = new Uuid(Uuid::v4());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferredEmail(): ?string
    {
        return $this->referredEmail;
    }

    public function setReferredEmail(string $referredEmail): static
    {
        $this->referredEmail = $referredEmail;

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

    public function getReferredBy(): ?ReferrerProfile
    {
        return $this->referredBy;
    }

    public function setReferredBy(?ReferrerProfile $referredBy): static
    {
        $this->referredBy = $referredBy;

        return $this;
    }

    public function getReferralCode(): ?Uuid
    {
        return $this->referralCode;
    }

    public function setReferralCode(Uuid $referralCode): static
    {
        $this->referralCode = $referralCode;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function setStep(int $step): static
    {
        $this->step = $step;

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

    public function getRewards(): ?string
    {
        return $this->rewards;
    }

    public function setRewards(?string $rewards): static
    {
        $this->rewards = $rewards;

        return $this;
    }
}
