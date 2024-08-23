<?php

namespace App\Data\BusinessModel;

use App\Entity\User;

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
     * @var null|string
     */
    public $reference;

    /**
     * @var null|User
     */
    public $user;

    /**
     * @var null|string
     */
    public $status;

    /**
     * @var null|string
     */
    public $type;
}