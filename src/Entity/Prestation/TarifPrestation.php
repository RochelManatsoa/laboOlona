<?php

namespace App\Entity\Prestation;

use App\Entity\Finance\Devise;
use App\Entity\Prestation;
use App\Repository\Prestation\TarifPrestationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifPrestationRepository::class)]
class TarifPrestation
{
    const TYPE_HOURLY = 'HOURLY';
    const TYPE_DAILY = 'DAILY';
    const TYPE_MONTHLY = 'MONTHLY';
    const TYPE_PROJECT = 'PROJECT';

    public static function arrayTarifType() {
        return [
            'Horaire' => self::TYPE_HOURLY ,
            'Journalier' => self::TYPE_DAILY ,
            'Mensuel' => self::TYPE_MONTHLY ,
            'Par projet' => self::TYPE_PROJECT ,
        ];
    }
    
    public static function arrayInverseTarifType() {
        return [
            self::TYPE_HOURLY => 'Horaire',
            self::TYPE_DAILY => 'Journalier' ,
            self::TYPE_MONTHLY => 'Mensuel',
            self::TYPE_PROJECT => 'Par projet',
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(length: 50)]
    private ?string $typeTarif = null;

    #[ORM\ManyToOne(inversedBy: 'tarifPrestations')]
    private ?Devise $currency = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updateAt = null;

    #[ORM\OneToOne(inversedBy: 'tarifPrestation', cascade: ['persist', 'remove'])]
    private ?Prestation $prestation = null;



    public function __toString(): string
    {
        $tarif = $this->getMontant();
        if($this->getCurrency()){
            $symbole = $this->getCurrency()->getSymbole();
        }
        switch ($this->getTypeTarif()) {
            case self::TYPE_HOURLY :
                $tarif = $this->getMontant().' '.$symbole.' /heure';
                break;
            
            case self::TYPE_DAILY :
                $tarif = $this->getMontant().' '.$symbole.' /jour';
                break;

            case self::TYPE_MONTHLY :
                $tarif = $this->getMontant().' '.$symbole.' /mois';
                break;

            case self::TYPE_PROJECT :
                $tarif = $this->getMontant().' '.$symbole.' /projet';
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

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

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

    public function getCurrency(): ?Devise
    {
        return $this->currency;
    }

    public function setCurrency(?Devise $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTimeInterface $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getPrestation(): ?Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(?Prestation $prestation): static
    {
        $this->prestation = $prestation;

        return $this;
    }
}
