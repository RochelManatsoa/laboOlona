<?php

namespace App\Entity\BusinessModel;

use App\Repository\BusinessModel\TypeTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeTransactionRepository::class)]
class TypeTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isValid = null;

    #[ORM\OneToMany(mappedBy: 'typeTransaction', targetEntity: Transaction::class)]
    private Collection $transaction;

    #[ORM\OneToMany(mappedBy: 'typeTransaction', targetEntity: TransactionReference::class)]
    private Collection $transactionReferences;

    public function __construct()
    {
        $this->transaction = new ArrayCollection();
        $this->transactionReferences = new ArrayCollection();
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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

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

    public function isIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(?bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransaction(): Collection
    {
        return $this->transaction;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transaction->contains($transaction)) {
            $this->transaction->add($transaction);
            $transaction->setTypeTransaction($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transaction->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getTypeTransaction() === $this) {
                $transaction->setTypeTransaction(null);
            }
        }

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
            $transactionReference->setTypeTransaction($this);
        }

        return $this;
    }

    public function removeTransactionReference(TransactionReference $transactionReference): static
    {
        if ($this->transactionReferences->removeElement($transactionReference)) {
            // set the owning side to null (unless already changed)
            if ($transactionReference->getTypeTransaction() === $this) {
                $transactionReference->setTypeTransaction(null);
            }
        }

        return $this;
    }
}
