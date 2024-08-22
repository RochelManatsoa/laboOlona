<?php

namespace App\Entity\BusinessModel;

use App\Entity\User;
use App\Repository\BusinessModel\CreditRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreditRepository::class)]
class Credit
{
    const ACTION_VIEW_CANDIDATE = 'VIEW_CANDIDATE';
    const ACTION_VIEW_RECRUITER = 'VIEW_RECRUITER';
    const ACTION_APPLY_JOB = 'APPLY_JOB';
    const ACTION_APPLY_OFFER = 'APPLY_OFFER';
    const ACTION_APPLY_PRESTATION_CANDIDATE = 'APPLY_PRESTATION_CANDIDATE';
    const ACTION_APPLY_PRESTATION_RECRUITER = 'APPLY_PRESTATION_RECRUITER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $total = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expireAt = null;

    #[ORM\OneToOne(inversedBy: 'credit', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pass = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(?int $total): static
    {
        $this->total = $total;

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

    public function getExpireAt(): ?\DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTimeInterface $expireAt): static
    {
        $this->expireAt = $expireAt;

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

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(?string $pass): static
    {
        $this->pass = $pass;

        return $this;
    }
}
