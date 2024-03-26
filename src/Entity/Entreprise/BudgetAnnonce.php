<?php

namespace App\Entity\Entreprise;

use App\Repository\Entreprise\BudgetAnnonceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BudgetAnnonceRepository::class)]
class BudgetAnnonce
{
    const TYPE_PONCTUAL = 'PONCTUAL';
    const TYPE_MONTHLY = 'MONTHLY';
    const DEVISE_EUR = 'EUR';
    const DEVISE_USD = 'USD';
    const DEVISE_AR = 'AR';

    public static function arrayTarifType() {
        return [
            'Mensuel' => self::TYPE_MONTHLY ,
            'Ponctuel' => self::TYPE_PONCTUAL ,
        ];
    }

    public static function arrayInverseDevise() {
        return [
            self::DEVISE_EUR => '€',
            self::DEVISE_USD => '$',
            self::DEVISE_AR => 'Ar',
        ];
    }

    public static function arrayDevise() {
        return [
            '€' => self::DEVISE_EUR ,
            '$' => self::DEVISE_USD ,
            'Ar' => self::DEVISE_AR ,
        ];
    }

    public static function getDeviseSymbol(string $devise): ?string {
        $deviseArray = self::arrayInverseDevise();
        return $deviseArray[$devise] ?? null; 
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $devise = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeBudget = null;

    #[ORM\OneToMany(mappedBy: 'budgetAnnonce', targetEntity: JobListing::class)]
    private Collection $annonce;

    public function __construct()
    {
        $this->annonce = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(?string $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getTypeBudget(): ?string
    {
        return $this->typeBudget;
    }

    public function setTypeBudget(?string $typeBudget): static
    {
        $this->typeBudget = $typeBudget;

        return $this;
    }

    /**
     * @return Collection<int, JobListing>
     */
    public function getAnnonce(): Collection
    {
        return $this->annonce;
    }

    public function addAnnonce(JobListing $annonce): static
    {
        if (!$this->annonce->contains($annonce)) {
            $this->annonce->add($annonce);
            $annonce->setBudgetAnnonce($this);
        }

        return $this;
    }

    public function removeAnnonce(JobListing $annonce): static
    {
        if ($this->annonce->removeElement($annonce)) {
            // set the owning side to null (unless already changed)
            if ($annonce->getBudgetAnnonce() === $this) {
                $annonce->setBudgetAnnonce(null);
            }
        }

        return $this;
    }
}
