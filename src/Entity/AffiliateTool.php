<?php

namespace App\Entity;

use App\Entity\AffiliateTool\Category;
use App\Entity\AffiliateTool\Tag;
use App\Repository\AffiliateToolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffiliateToolRepository::class)]
class AffiliateTool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $lienAffiliation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commission = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?int $customId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slogan = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $editeLe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?bool $featured = null;

    #[ORM\Column(nullable: true)]
    private ?array $relatedIds = null;

    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'affiliateTool', cascade: ['persist'])]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'affiliateTool', cascade: ['persist'])]
    private Collection $tags;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionFr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sloganFr = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescriptionFr = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLienAffiliation(): ?string
    {
        return $this->lienAffiliation;
    }

    public function setLienAffiliation(string $lienAffiliation): static
    {
        $this->lienAffiliation = $lienAffiliation;

        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(string $commission): static
    {
        $this->commission = $commission;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCustomId(): ?int
    {
        return $this->customId;
    }

    public function setCustomId(?int $customId): static
    {
        $this->customId = $customId;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getSlogan(): ?string
    {
        return $this->slogan;
    }

    public function setSlogan(?string $slogan): static
    {
        $this->slogan = $slogan;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

        return $this;
    }

    public function getEditeLe(): ?\DateTimeInterface
    {
        return $this->editeLe;
    }

    public function setEditeLe(?\DateTimeInterface $editeLe): static
    {
        $this->editeLe = $editeLe;

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

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(?bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getRelatedIds(): ?array
    {
        return $this->relatedIds;
    }

    public function setRelatedIds(?array $relatedIds): static
    {
        $this->relatedIds = $relatedIds;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addAffiliateTool($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeAffiliateTool($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addAffiliateTool($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeAffiliateTool($this);
        }

        return $this;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(?string $descriptionEn): static
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    public function getDescriptionFr(): ?string
    {
        return $this->descriptionFr;
    }

    public function setDescriptionFr(?string $descriptionFr): static
    {
        $this->descriptionFr = $descriptionFr;

        return $this;
    }

    public function getSloganFr(): ?string
    {
        return $this->sloganFr;
    }

    public function setSloganFr(?string $sloganFr): static
    {
        $this->sloganFr = $sloganFr;

        return $this;
    }

    public function getShortDescriptionFr(): ?string
    {
        return $this->shortDescriptionFr;
    }

    public function setShortDescriptionFr(?string $shortDescriptionFr): static
    {
        $this->shortDescriptionFr = $shortDescriptionFr;

        return $this;
    }
}
