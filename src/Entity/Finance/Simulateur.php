<?php

namespace App\Entity\Finance;

use App\Entity\Candidate\TarifCandidat;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Entity\Finance\Contrat;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Finance\SimulateurRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SimulateurRepository::class)]
class Simulateur
{    
    const STATUS_LIBRE = 'LIBRE';
    const STATUS_SEND = 'SEND';
    const STATUS_CONTACT = 'CONTACT';
    const STATUS_RELANCE = 'RELANCE';
    const STATUS_CLIENT = 'CLIENT';


    public static function getStatuses() {
        return [
            'Simulation libre' => self::STATUS_LIBRE ,
            'Demande envoyée' => self::STATUS_SEND ,
            'Prise de contact' => self::STATUS_CONTACT ,
            'Relance' => self::STATUS_RELANCE ,
            'Client' => self::STATUS_CLIENT ,
        ];
    }

    public static function getArrayStatuses() {
        return [
             self::STATUS_LIBRE ,
             self::STATUS_SEND ,
             self::STATUS_CONTACT ,
             self::STATUS_RELANCE ,
             self::STATUS_CLIENT ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['simulateur'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['simulateur'])]
    private ?float $salaireNet = null;

    #[ORM\Column]
    private ?float $taux = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreEnfant = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Avantage $avantage = null;

    #[ORM\OneToOne(mappedBy: 'simulateur', cascade: ['persist', 'remove'])]
    private ?Contrat $contrat = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?float $forfait = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['simulateur'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'simulateurs', cascade: ['persist', 'remove'])]
    private ?Devise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'simulateurs', cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: true)]
    private ?int $jourRepas = null;

    #[ORM\Column(nullable: true)]
    private ?int $jourDeplacement = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixRepas = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixDeplacement = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $deviseSymbole = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $primeNet = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDeleted = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statusFinance = null;

    #[ORM\OneToMany(mappedBy: 'simulation', targetEntity: TarifCandidat::class)]
    private Collection $tarifCandidats;

    public function __construct()
    {
        $this->statusFinance = self::STATUS_LIBRE;
        $this->tarifCandidats = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalaireNet(): ?float
    {
        return $this->salaireNet;
    }

    public function setSalaireNet(float $salaireNet): static
    {
        $this->salaireNet = $salaireNet;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(float $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getNombreEnfant(): ?int
    {
        return $this->nombreEnfant;
    }

    public function setNombreEnfant(?int $nombreEnfant): static
    {
        $this->nombreEnfant = $nombreEnfant;

        return $this;
    }

    public function getAvantage(): ?Avantage
    {
        return $this->avantage;
    }

    public function setAvantage(?Avantage $avantage): static
    {
        $this->avantage = $avantage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getForfait(): ?float
    {
        return $this->forfait;
    }

    public function setForfait(?float $forfait): static
    {
        $this->forfait = $forfait;

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

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;

        return $this;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        // unset the owning side of the relation if necessary
        if ($contrat === null && $this->contrat !== null) {
            $this->contrat->setEmploye(null);
        }

        // set the owning side of the relation if necessary
        if ($contrat !== null && $contrat->getEmploye() !== $this) {
            $contrat->setEmploye($this->getEmploye());
        }

        $this->contrat = $contrat;

        return $this;
    }

    public function getJourRepas(): ?int
    {
        return $this->jourRepas;
    }

    public function setJourRepas(?int $jourRepas): static
    {
        $this->jourRepas = $jourRepas;

        return $this;
    }

    public function getJourDeplacement(): ?int
    {
        return $this->jourDeplacement;
    }

    public function setJourDeplacement(?int $jourDeplacement): static
    {
        $this->jourDeplacement = $jourDeplacement;

        return $this;
    }

    public function getPrixRepas(): ?float
    {
        return $this->prixRepas;
    }

    public function setPrixRepas(?float $prixRepas): static
    {
        $this->prixRepas = $prixRepas;

        return $this;
    }

    public function getPrixDeplacement(): ?float
    {
        return $this->prixDeplacement;
    }

    public function setPrixDeplacement(?float $prixDeplacement): static
    {
        $this->prixDeplacement = $prixDeplacement;

        return $this;
    }

    public function getDeviseSymbole(): ?string
    {
        return $this->deviseSymbole;
    }

    public function setDeviseSymbole(?string $deviseSymbole): static
    {
        $this->deviseSymbole = $deviseSymbole;

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

    public function getPrimeNet(): ?float
    {
        return $this->primeNet;
    }

    public function setPrimeNet(?float $primeNet): static
    {
        $this->primeNet = $primeNet;

        return $this;
    }

    public function isIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getStatusFinance(): ?string
    {
        return $this->statusFinance;
    }

    public function setStatusFinance(?string $statusFinance): static
    {
        $this->statusFinance = $statusFinance;

        return $this;
    }

    /**
     * @return Collection<int, TarifCandidat>
     */
    public function getTarifCandidats(): Collection
    {
        return $this->tarifCandidats;
    }

    public function addTarifCandidat(TarifCandidat $tarifCandidat): static
    {
        if (!$this->tarifCandidats->contains($tarifCandidat)) {
            $this->tarifCandidats->add($tarifCandidat);
            $tarifCandidat->setSimulation($this);
        }

        return $this;
    }

    public function removeTarifCandidat(TarifCandidat $tarifCandidat): static
    {
        if ($this->tarifCandidats->removeElement($tarifCandidat)) {
            // set the owning side to null (unless already changed)
            if ($tarifCandidat->getSimulation() === $this) {
                $tarifCandidat->setSimulation(null);
            }
        }

        return $this;
    }
}
