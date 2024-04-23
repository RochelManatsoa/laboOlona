<?php

namespace App\Entity\Moderateur;

use App\Entity\Finance\Devise;
use App\Repository\Moderateur\ForfaitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForfaitRepository::class)]
class Forfait
{
    const TYPE_HOURLY = 'HOURLY';
    const TYPE_DAILY = 'DAILY';
    const TYPE_MONTHLY = 'MONTHLY';
    const DEVISE_EUR = 'EUR';
    const DEVISE_USD = 'USD';
    const DEVISE_AR = 'AR';

    public static function arrayTarifType() {
        return [
            'Horaire' => self::TYPE_HOURLY ,
            'Journalier' => self::TYPE_DAILY ,
            'Mensuel' => self::TYPE_MONTHLY ,
        ];
    }
    public static function arrayInverseTarifType() {
        return [
            self::TYPE_HOURLY => 'Horaire',
            self::TYPE_DAILY => 'Journalier' ,
            self::TYPE_MONTHLY => 'Mensuel',
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
    private ?string $typeForfait = null;

    #[ORM\OneToOne(inversedBy: 'forfaitAssignation', cascade: ['persist', 'remove'])]
    private ?Assignation $assignation = null;

    #[ORM\ManyToOne]
    private ?Devise $currency = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getTypeForfait(): ?string
    {
        return $this->typeForfait;
    }

    public function setTypeForfait(?string $typeForfait): static
    {
        $this->typeForfait = $typeForfait;

        return $this;
    }

    public function getAssignation(): ?Assignation
    {
        return $this->assignation;
    }

    public function setAssignation(?Assignation $assignation): static
    {
        $this->assignation = $assignation;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
