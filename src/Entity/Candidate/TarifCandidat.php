<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Finance\Devise;
use App\Repository\Candidate\TarifCandidatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifCandidatRepository::class)]
class TarifCandidat
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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0')]
    private ?string $montant = null;

    #[ORM\Column(length: 50)]
    private ?string $devise = self::DEVISE_EUR;

    #[ORM\Column(length: 50)]
    private ?string $typeTarif = self::TYPE_HOURLY;

    #[ORM\OneToOne(inversedBy: 'tarifCandidat', cascade: ['persist', 'remove'])]
    private ?CandidateProfile $candidat = null;

    #[ORM\ManyToOne]
    private ?Devise $currency = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __toString(): string
    {
        $tarif = $this->getMontant();
        $symbole = $this->getDeviseSymbol($this->getDevise());
        if($this->getCurrency()){
            $symbole = $this->getCurrency()->getSymbole();
        }
        switch ($this->getTypeTarif()) {
            case TarifCandidat::TYPE_HOURLY :
                $tarif = $this->getMontant().' '.$symbole.' /heure';
                break;
            
            case TarifCandidat::TYPE_DAILY :
                $tarif = $this->getMontant().' '.$symbole.' /jour';
                break;

            case TarifCandidat::TYPE_MONTHLY :
                $tarif = $this->getMontant().' '.$symbole.' /mois';
                break;
        }
        return $tarif;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise ?? ''; // Transforme `null` en chaîne vide
        return $this;
    }

    public function getTypeTarif(): ?string
    {
        return $this->typeTarif;
    }

    public function setTypeTarif(string $typeTarif): static
    {
        $this->typeTarif = $typeTarif;

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

    public function getCurrency(): ?Devise
    {
        return $this->currency;
    }

    public function setCurrency(?Devise $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
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
}
