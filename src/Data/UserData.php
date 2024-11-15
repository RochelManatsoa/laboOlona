<?php

namespace App\Data;

use DateTime;
use Google\Service\AdExchangeBuyerII\Date;

class UserData
{

    /**
     * @var null|integer
     */
    public $page = 1;

    /**
     * @var null|DateTime
     */
    public $startDate;

    /**
     * @var null|DateTime
     */
    public $endDate;

    /**
     * * @var null|integer
     */
    public $days;

    public function __construct(?DateTime $startDate, ?DateTime $endDate, int $page = 1, int $days = 1)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->page = $page;
        $this->days = $days;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getDays(): int
    {
        return $this->days;
    }

}