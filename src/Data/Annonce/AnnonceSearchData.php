<?php

namespace App\Data\Annonce;

use App\Entity\EntrepriseProfile;

class AnnonceSearchData
{

    /**
     * @var null|string
     */
    public $q;

    /**
     * @var null|integer
     */
    public $page = 1;

    /**
     * @var null|EntrepriseProfile
     */
    public $entreprise;

    /**
     * @var null|string
     */
    public $status;

    /**
     * @var null|integer
     */
    public $experiences;

    /**
     * @var null|integer
     */
    public $competences;

    /**
     * @var null|integer
     */
    public $secteurs;

    /**
     * @var null|integer
     */
    public $tarif;
}