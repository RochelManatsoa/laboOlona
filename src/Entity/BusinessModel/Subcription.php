<?php

namespace App\Entity\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Repository\BusinessModel\SubcriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubcriptionRepository::class)]
class Subcription
{
    const TYPE_ENTREPRISE = 'ENTREPRISE';
    const TYPE_CANDIDAT = 'CANDIDAT';

    public static function getTypes() {
        return [
            'Entreprise' => self::TYPE_ENTREPRISE ,
            'Candidat' => self::TYPE_CANDIDAT ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::TYPE_ENTREPRISE => 'Entreprise' ,
            self::TYPE_CANDIDAT => 'Candidat' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subcriptions')]
    private ?EntrepriseProfile $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'subcriptions')]
    private ?CandidateProfile $candidat = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $relance = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $endDate = null;

    public function __construct()
    {
        $this->setCreatedAt(new  \DateTime());
        $this->setRelance(0);
    }

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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRelance(): ?int
    {
        return $this->relance;
    }

    public function setRelance(?int $relance): static
    {
        $this->relance = $relance;

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function calculerFin(): void
    {
        if ($this->startDate && $this->duration) {
            $fin = clone $this->startDate;
            $fin->modify("+{$this->duration} months");
            $this->endDate = $fin;
        }
    }
}
