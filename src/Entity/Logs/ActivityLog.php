<?php

namespace App\Entity\Logs;

use App\Entity\User;
use App\Repository\Logs\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
class ActivityLog
{
    const LEVEL_INFO = 1;
    const LEVEL_NOTICE = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_CRITICAL = 4;

    const ACTIVITY_LOGIN = 'Connexion';
    const ACTIVITY_SEARCH = 'Recherche';
    const ACTIVITY_PAGE_VIEW = 'Page Vue';
    const ACTIVITY_PURCHASE = 'Achat';
    const ACTIVITY_CREDIT_SPENDING = 'Crédit';
    const ACTIVITY_ERROR = 'Erreur';
    const ACTIVITY_REGISTER = 'Inscription';
    const ACTIVITY_UPDATE = 'Mise à jour';
    const ACTIVITY_DELETE = 'Suppression';
    const ACTIVITY_CREATE = 'Création';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'activityLogs', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $activityType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?int $level = self::LEVEL_INFO;

    #[ORM\Column]
    private ?int $userCredit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pageUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $derationInSeconds = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deviceType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referrer = null;

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

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): static
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getUserCredit(): ?int
    {
        return $this->userCredit;
    }

    public function setUserCredit(int $userCredit): static
    {
        $this->userCredit = $userCredit;

        return $this;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    public function setPageUrl(?string $pageUrl): static
    {
        $this->pageUrl = $pageUrl;

        return $this;
    }

    public function getDerationInSeconds(): ?int
    {
        return $this->derationInSeconds;
    }

    public function setDerationInSeconds(?int $derationInSeconds): static
    {
        $this->derationInSeconds = $derationInSeconds;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): static
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function setReferrer(?string $referrer): static
    {
        $this->referrer = $referrer;

        return $this;
    }
}
