<?php

namespace App\Entity\BusinessModel;

use App\Entity\Finance\Devise;
use App\Entity\User;
use App\Repository\BusinessModel\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
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
    const DOC_DOWNLOAD = 'documents/';

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

    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalAmount = null;

    #[ORM\ManyToOne]
    private ?Devise $currency = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payerId = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $customer = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?TypeTransaction $paymentMethod = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Package $package = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\OneToOne(inversedBy: 'command', cascade: ['persist', 'remove'])]
    private ?Transaction $transaction = null;

    #[ORM\OneToOne(mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Invoice $invoice = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
    }

    #[ORM\PrePersist]
    public function generateOrderNumber(): void
    {
        if ($this->orderNumber === null) {
            $this->orderNumber = $this->generateUniqueOrderNumber();
        }
    }

    private function generateUniqueOrderNumber(): string
    {
        return uniqid('order_', true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

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

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getCurrency(): ?Devise
    {
        return $this->currency;
    }

    public function setCurrency(?Devise $currency): static
    {
        $this->currency = $currency;

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

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): static
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function getPayerId(): ?string
    {
        return $this->payerId;
    }

    public function setPayerId(?string $payerId): static
    {
        $this->payerId = $payerId;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPaymentMethod(): ?TypeTransaction
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?TypeTransaction $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getGeneratedFacturePathFile(): ?string
    {

        return $this->getGeneratedFacturePath().'/facture.pdf';
    }

    public function getGeneratedFacturePath(): ?string
    {
        $path = $this::DOC_DOWNLOAD . $this->id ."/".
            $this->orderNumber;

        return $path;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        // unset the owning side of the relation if necessary
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setCommande(null);
        }

        // set the owning side of the relation if necessary
        if ($invoice !== null && $invoice->getCommande() !== $this) {
            $invoice->setCommande($this);
        }

        $this->invoice = $invoice;

        return $this;
    }
}
