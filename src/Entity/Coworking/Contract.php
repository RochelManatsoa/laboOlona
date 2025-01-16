<?php

namespace App\Entity\Coworking;

use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Package;
use App\Entity\User;
use App\Repository\Coworking\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: '`contract_coworking`')]
#[ORM\HasLifecycleCallbacks]
class Contract
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_VALIDATED = 'VALIDATED';
    const STATUS_NOT_PAID = 'NOT_PAID';
    const STATUS_ARCHIVED = 'ARCHIVED';   
    const DOC_DOWNLOAD = 'contrats/';

    public static function getStatuses() {
        return [
            'En attente de confirmation' => self::STATUS_PENDING ,
            'En attente de paiement' => self::STATUS_NOT_PAID ,
            'Validé' => self::STATUS_VALIDATED ,
            'Archivé' => self::STATUS_ARCHIVED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>        'En attente de confirmation' ,
            self::STATUS_NOT_PAID =>      'En attente de paiement' ,
            self::STATUS_VALIDATED =>      'Validé' ,
            self::STATUS_ARCHIVED =>       'Archivé' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $typePerson = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $socialReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adress = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $contractNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    private ?Package $package = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'contract', cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
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
        return uniqid('contract_', true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isTypePerson(): ?bool
    {
        return $this->typePerson;
    }

    public function setTypePerson(?bool $typePerson): static
    {
        $this->typePerson = $typePerson;

        return $this;
    }

    public function getSocialReason(): ?string
    {
        return $this->socialReason;
    }

    public function setSocialReason(?string $socialReason): static
    {
        $this->socialReason = $socialReason;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
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

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getGeneratedContractPathFile(): ?string
    {
        return $this->getGeneratedDocsPath().'/contrat.pdf';
    }

    public function getGeneratedFacturePathFile(): ?string
    {
        return $this->getGeneratedDocsPath().'/facture.pdf';
    }

    public function getGeneratedDocsPath(): ?string
    {
        $path = $this::DOC_DOWNLOAD . $this->id ."/".
            $this->contractNumber;

        return $path;
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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }
}
