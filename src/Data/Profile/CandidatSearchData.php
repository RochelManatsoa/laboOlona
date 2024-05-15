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
    public $matricule;
}