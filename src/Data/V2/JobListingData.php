<?php

namespace App\Data\V2;

use App\Entity\EntrepriseProfile;

class JobListingData
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
}