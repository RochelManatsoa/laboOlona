<?php

namespace App\Data\Finance;

use App\Entity\Finance\Employe;

class SearchData
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
     * @var []|array
     */
    public $salaires;

    /**
     * @var null|Employe
     */
    public $employe;

    /**
     * @var null|string
     */
    public $status;

    /**
     * @var null|string
     */
    public $statusDemande;

    /**
     * @var null|string
     */
    public $type;
}