<?php

namespace App\Entity;

use App\Entity\Referrer\Referral;
use App\Repository\ReferrerProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferrerProfileRepository::class)]
class ReferrerProfile
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_BANNISHED = 'BANNISHED';
    const STATUS_VALID = 'VALID';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'referrerProfile', cascade: ['persist', 'remove'])]
    private ?User $referrer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $raisonSocial = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nif = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statutJuridique = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $creation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adressePro = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephonePro = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailPro = null;

    #[ORM\Column(length: 255)]
    private ?string $customId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\OneToMany(mappedBy: 'referredBy', targetEntity: Referral::class)]
    private Collection $referrals;

    #[ORM\Column(nullable: true)]
    private ?int $totalRewards = 0;

    #[ORM\Column(nullable: true)]
    private ?int $pendingRewards = 0;

    public function __construct()
    {
        $this->referrals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferrer(): ?User
    {
        return $this->referrer;
    }

    public function setReferrer(?User $referrer): static
    {
        $this->referrer = $referrer;

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

    public function getRaisonSocial(): ?string
    {
        return $this->raisonSocial;
    }

    public function setRaisonSocial(?string $raisonSocial): static
    {
        $this->raisonSocial = $raisonSocial;

        return $this;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function setNif(?string $nif): static
    {
        $this->nif = $nif;

        return $this;
    }

    public function getStatutJuridique(): ?string
    {
        return $this->statutJuridique;
    }

    public function setStatutJuridique(?string $statutJuridique): static
    {
        $this->statutJuridique = $statutJuridique;

        return $this;
    }

    public function getCreation(): ?\DateTimeInterface
    {
        return $this->creation;
    }

    public function setCreation(?\DateTimeInterface $creation): static
    {
        $this->creation = $creation;

        return $this;
    }

    public function getAdressePro(): ?string
    {
        return $this->adressePro;
    }

    public function setAdressePro(?string $adressePro): static
    {
        $this->adressePro = $adressePro;

        return $this;
    }

    public function getTelephonePro(): ?string
    {
        return $this->telephonePro;
    }

    public function setTelephonePro(?string $telephonePro): static
    {
        $this->telephonePro = $telephonePro;

        return $this;
    }

    public function getEmailPro(): ?string
    {
        return $this->emailPro;
    }

    public function setEmailPro(?string $emailPro): static
    {
        $this->emailPro = $emailPro;

        return $this;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(string $customId): static
    {
        $this->customId = $customId;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Referral>
     */
    public function getReferrals(): Collection
    {
        return $this->referrals;
    }

    public function addReferral(Referral $referral): static
    {
        if (!$this->referrals->contains($referral)) {
            $this->referrals->add($referral);
            $referral->setReferredBy($this);
        }

        return $this;
    }

    public function removeReferral(Referral $referral): static
    {
        if ($this->referrals->removeElement($referral)) {
            // set the owning side to null (unless already changed)
            if ($referral->getReferredBy() === $this) {
                $referral->setReferredBy(null);
            }
        }

        return $this;
    }

    public function getTotalRewards(): ?int
    {
        return $this->totalRewards;
    }

    public function setTotalRewards(?int $totalRewards): static
    {
        $this->totalRewards = $totalRewards;

        return $this;
    }

    public function getPendingRewards(): ?int
    {
        return $this->pendingRewards;
    }

    public function setPendingRewards(?int $pendingRewards): static
    {
        $this->pendingRewards = $pendingRewards;

        return $this;
    }
}
