<?php
namespace App\Entity\Enum;

enum StatusApplication: string
{
    case Pending = "PENDING";
    case Accepted = "ACCEPTED";
    case Refused = "REFUSED";
}