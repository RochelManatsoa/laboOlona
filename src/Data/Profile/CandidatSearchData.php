<?php

namespace App\Data\Profile;

use App\Entity\CandidateProfile;

class CandidatSearchData
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
     * @var null|CandidateProfile
     */
    public $candidat;

    /**
     * @var null|string
     */
    public $status;

    /**
     * @var null|integer
     */
    public $cv;

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
    public $relance;

    /**
     * @var null|integer
     */
    public $resume;

    /**
     * @var null|integer
     */
    public $level;

    /**
     * @var null|integer
     */
    public $dispo;

    /**
     * @var null|integer
     */
    public $tarif;

    /**
     * @var null|integer
     */
    public $avatar;

    /**
     * @var null|integer
     */
    public $matricule;

    /**
     * @var null|integer
     */
    public $province;
    
    /**
     * @var null|integer
     */
    public $region;
    
    /**
     * @var null|integer
     */
    public $gender;
}