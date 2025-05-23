<?php

namespace App\Entity\Coworking;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\Coworking\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    const STATUS_RESERVED = 'RESERVED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELLED = 'CANCELLED';   

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Confirmée' => self::STATUS_CONFIRMED ,
            'Réservée' => self::STATUS_RESERVED ,
            'Payée' => self::STATUS_PAID ,
            'Annulée' => self::STATUS_CANCELLED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>         'En attente' ,
            self::STATUS_CONFIRMED =>       'Confirmée' ,
            self::STATUS_CANCELLED =>       'Annulée' ,
            self::STATUS_RESERVED =>        'Réservée' ,
            self::STATUS_PAID =>            'Payée' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endEvent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startEvent = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $allDay = null;

    #[ORM\Column(length: 7)]
    private ?string $backgroundColor = null;

    #[ORM\Column(length: 7)]
    private ?string $borderColor = null;

    #[ORM\Column(length: 7)]
    private ?string $textColor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Place::class, inversedBy: 'events')]
    private Collection $places;

    #[ORM\Column(length: 20)]
    private ?string $duration = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new \DateTime();
        $this->places = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getEndEvent(): ?\DateTimeInterface
    {
        return $this->endEvent;
    }

    public function setEndEvent(\DateTimeInterface $endEvent): static
    {
        $this->endEvent = $endEvent;

        return $this;
    }

    public function getStartEvent(): ?\DateTimeInterface
    {
        return $this->startEvent;
    }

    public function setStartEvent(\DateTimeInterface $startEvent): static
    {
        $this->startEvent = $startEvent;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    public function setBorderColor(string $borderColor): static
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(string $textColor): static
    {
        $this->textColor = $textColor;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Place>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    public function addPlace(Place $place): static
    {
        if (!$this->places->contains($place)) {
            $this->places->add($place);
        }

        return $this;
    }

    public function removePlace(Place $place): static
    {
        $this->places->removeElement($place);

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): static
    {
        $this->duration = $duration;

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
}
