<?php

namespace App\Entity\AffiliateTool;

use App\Entity\AffiliateTool;
use App\Repository\AffiliateTool\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: AffiliateTool::class, inversedBy: 'categories', cascade: ['persist'])]
    private Collection $affiliateTool;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomFr = null;

    public function __toString()
    {
        return $this->nom;
    }

    public function __construct()
    {
        $this->affiliateTool = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    /**
     * @return Collection<int, AffiliateTool>
     */
    public function getAffiliateTool(): Collection
    {
        return $this->affiliateTool;
    }

    public function addAffiliateTool(AffiliateTool $affiliateTool): static
    {
        if (!$this->affiliateTool->contains($affiliateTool)) {
            $this->affiliateTool->add($affiliateTool);
        }

        return $this;
    }

    public function removeAffiliateTool(AffiliateTool $affiliateTool): static
    {
        $this->affiliateTool->removeElement($affiliateTool);

        return $this;
    }

    public function getNomFr(): ?string
    {
        return $this->nomFr;
    }

    public function setNomFr(?string $nomFr): static
    {
        $this->nomFr = $nomFr;

        return $this;
    }
}
