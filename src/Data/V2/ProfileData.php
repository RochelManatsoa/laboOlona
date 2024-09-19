<?php

namespace App\Data\V2;

use App\Entity\CandidateProfile;

class ProfileData
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
     * @var null|EntrepriseProfile
     */
    public $entreprise;

    /**
     * @var null|string
     */
    public $status;
}