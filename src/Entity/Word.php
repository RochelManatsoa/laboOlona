<?php

namespace App\Entity;

use App\Repository\WordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordRepository::class)]
class Word
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?int $usageCount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUsageCount(): ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(?int $usageCount): static
    {
        $this->usageCount = $usageCount;

        return $this;
    }
}
