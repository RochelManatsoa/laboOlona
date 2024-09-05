<?php

namespace App\Entity\BusinessModel;

use App\Entity\User;
use App\Repository\BusinessModel\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_ON_HOLD = 'ON_HOLD';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_REFUNDED = 'REFUNDED';
    const STATUS_DISPUTED = 'DISPUTED';

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Complétée' => self::STATUS_COMPLETED ,
            'Échouée' => self::STATUS_FAILED ,
            'Annulée' => self::STATUS_CANCELLED ,
            'Ouverte' => self::STATUS_ON_HOLD ,
            'En traitement' => self::STATUS_PROCESSING ,
            'Autorisée' => self::STATUS_AUTHORIZED ,
            'Remboursée' => self::STATUS_REFUNDED ,
            'Contestée' => self::STATUS_DISPUTED ,
        ];
    }
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>         'En attente' ,
            self::STATUS_COMPLETED =>       'Complétée' ,
            self::STATUS_FAILED =>          'Échouée' ,
            self::STATUS_CANCELLED =>       'Annulée' ,
            self::STATUS_ON_HOLD =>         'Ouverte' ,
            self::STATUS_PROCESSING =>      'En traitement' ,
            self::STATUS_AUTHORIZED =>      'Autorisée' ,
            self::STATUS_REFUNDED =>        'Remboursée' ,
            self::STATUS_DISPUTED =>        'Contestée' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $creditsAdded = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $transactionDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Package $package = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'transaction')]
    private ?TypeTransaction $typeTransaction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: TransactionReference::class, cascade: ['persist', 'remove'])]
    private Collection $transactionReferences;

    public function __construct()
    {
        $this->transactionReferences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCreditsAdded(): ?int
    {
        return $this->creditsAdded;
    }

    public function setCreditsAdded(?int $creditsAdded): static
    {
        $this->creditsAdded = $creditsAdded;

        return $this;
    }

    public function getTransactionDate(): ?\DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?\DateTimeInterface $transactionDate): static
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

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

    public function getTypeTransaction(): ?TypeTransaction
    {
        return $this->typeTransaction;
    }

    public function setTypeTransaction(?TypeTransaction $typeTransaction): static
    {
        $this->typeTransaction = $typeTransaction;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, TransactionReference>
     */
    public function getTransactionReferences(): Collection
    {
        return $this->transactionReferences;
    }

    public function addTransactionReference(TransactionReference $transactionReference): static
    {
        if (!$this->transactionReferences->contains($transactionReference)) {
            $this->transactionReferences->add($transactionReference);
            $transactionReference->setTransaction($this);
        }

        return $this;
    }

    public function removeTransactionReference(TransactionReference $transactionReference): static
    {
        if ($this->transactionReferences->removeElement($transactionReference)) {
            // set the owning side to null (unless already changed)
            if ($transactionReference->getTransaction() === $this) {
                $transactionReference->setTransaction(null);
            }
        }

        return $this;
    }
}
