<?php

namespace App\Entity\BusinessModel;

use App\Entity\CandidateProfile;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\EntrepriseProfile;
use Doctrine\Common\Collections\Collection;
use App\Entity\BusinessModel\TypeTransaction;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\BusinessModel\SubcriptionRepository;

#[ORM\Entity(repositoryClass: SubcriptionRepository::class)]
class Subcription
{
    const TYPE_ENTREPRISE = 'ENTREPRISE';
    const TYPE_CANDIDAT = 'CANDIDAT';   
    const DOC_DOWNLOAD = 'subcriptions/';

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

    #[ORM\Column(nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $endDate = null;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'subcription', targetEntity: Invoice::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $invoices;

    #[ORM\ManyToOne(inversedBy: 'subcriptions')]
    private ?Package $package = null;

    #[ORM\Column(length: 255)]
    private ?string $contractNumber = null;

    public function __construct()
    {
        $this->setCreatedAt(new  \DateTime());
        $this->setRelance(0);
        $this->invoices = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function generateOrderNumber(): void
    {
        if ($this->contractNumber === null) {
            $this->contractNumber = $this->generateUniqueOrderNumber();
        }
    }

    private function generateUniqueOrderNumber(): string
    {
        return uniqid('subcription_', true);
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

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

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

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setSubcription($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getSubcription() === $this) {
                $invoice->setSubcription(null);
            }
        }

        return $this;
    }

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(?Package $package): static
    {
        $this->package = $package;

        return $this;
    }
    
    public function getLastTypeTransaction(): ?TypeTransaction
    {
        // On récupère les factures triées par date décroissante
        $lastInvoice = $this->invoices
            ->filter(fn($invoice) => $invoice->getCreatedAt() !== null)
            ->toArray();

        usort($lastInvoice, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        $lastInvoice = $lastInvoice[0] ?? null;

        if (!$lastInvoice) {
            return null;
        }

        $order = $lastInvoice->getCommande();
        if (!$order) {
            return null;
        }

        $transaction = $order->getTransaction();
        if (!$transaction) {
            return null;
        }

        return $transaction->getTypeTransaction();
    }

    public function getGeneratedContractPathFile(): ?string
    {
        return $this->getGeneratedDocsPath().'/contrat.pdf';
    }

    public function getGeneratedDocsPath(): ?string
    {
        $path = $this::DOC_DOWNLOAD . $this->id ."/".
            $this->contractNumber;

        return $path;
    }

}
