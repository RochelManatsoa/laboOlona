<?php

namespace App\Data\Profile;

use App\Entity\User;

class StatSearchData
{
    /**
     * @var null|integer
     */
    public $page = 1;

    /**
     * @var null|integer
     */
    public $from = null;

    /**
     * @var null|User
     */
    public $user;

    /**
     * @var null|Datetime
     */
    public $start;

    /**
     * @var null|Datetime
     */
    public $end;
}