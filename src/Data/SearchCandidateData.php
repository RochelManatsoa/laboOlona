<?php

namespace App\Data;

use App\Entity\Candidate\Competences;
use App\Entity\CandidateProfile;
use App\Entity\Langue;

class SearchCandidateData
{
    /** @var int */
    private $page = 1;

    /** @var Secteur[] */
    private $secteurs = [];

    /** @var CandidateProfile[] */
    private $titre = [];

    /** @var array|null */
    
    private $test;

    /** @var Competences[] */
    private $competences = [];

    /** @var Langues[] */
    private $langue = [];

    // Getters
    public function getSecteurs(): array
    {
        return $this->secteurs;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTitre(): array
    {
        return $this->titre;
    }

    public function getCompetences(): array
    {
        return $this->competences;
    }

    public function getLangue(): array
    {
        return $this->langue;
    }

    // Setters
    public function setSecteurs(?array $secteurs): void
    {
        $this->secteurs = $secteurs ?: [];
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setTitre(?array $titre): void
    {
        if ($titre instanceof \Doctrine\Common\Collections\ArrayCollection) {
        $this->titre = $titre->toArray();
        } else {
        $this->titre = $titre;
        }
    }
    
    public function setCompetences(?array $competences): void
    {
        $this->competences = $competences ?: [];
    }

    public function setLangue(?array $langue): void
    {
        $this->langue = $langue ?: [];
    }
}
