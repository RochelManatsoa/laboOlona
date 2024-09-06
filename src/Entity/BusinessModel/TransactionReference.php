<?php

namespace App\Entity\BusinessModel;

use App\Repository\BusinessModel\TransactionReferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionReferenceRepository::class)]
class TransactionReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Sequentially([
        new Assert\NotNull,
        new Assert\Length(min:10, minMessage:'La référence est trop courte.'),
        new Assert\Regex(pattern: '/^[a-zA-Z0-9]*$/', message: 'La référence ne doit contenir que des chiffres et des lettres.'),
    ])]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'transactionReferences')]
    private ?TypeTransaction $typeTransaction = null;

    #[ORM\ManyToOne(inversedBy: 'transactionReferences')]
    private ?Transaction $transaction = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Sequentially([
        new Assert\NotNull,
        new Assert\Length(min:2, minMessage:'Le montant est trop courte.'),
        new Assert\Regex(pattern: '/^-?[0-9]+(\.[0-9]+)?$/', message: 'Le montant doit être un nombre décimal valide.'),
    ])]
    private ?float $montant = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeTransaction(): ?TypeTransaction
    {
        return $this->typeTransaction;
    }

    public function setTypeTransaction(?TypeTransaction $typeTransaction): static
    {
        $this->typeTransaction = $typeTransaction;

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

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }
}
