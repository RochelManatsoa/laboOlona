<?php

namespace App\Entity\Facebook;

use App\Repository\Facebook\ContestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestRepository::class)]
class Contest
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_ARCHIVED = 'ARCHIVED';   

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Publiée' => self::STATUS_PUBLISHED ,
            'Archivée' => self::STATUS_ARCHIVED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>        'En attente' ,
            self::STATUS_PUBLISHED =>      'Publié' ,
            self::STATUS_ARCHIVED =>       'Archivé' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'contest', targetEntity: ContestEntry::class)]
    private Collection $contestEntries;

    #[ORM\Column(length: 10)]
    private ?string $status = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->contestEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    /**
     * @return Collection<int, ContestEntry>
     */
    public function getContestEntries(): Collection
    {
        return $this->contestEntries;
    }

    public function addContestEntry(ContestEntry $contestEntry): static
    {
        if (!$this->contestEntries->contains($contestEntry)) {
            $this->contestEntries->add($contestEntry);
            $contestEntry->setContest($this);
        }

        return $this;
    }

    public function removeContestEntry(ContestEntry $contestEntry): static
    {
        if ($this->contestEntries->removeElement($contestEntry)) {
            // set the owning side to null (unless already changed)
            if ($contestEntry->getContest() === $this) {
                $contestEntry->setContest(null);
            }
        }

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
}
