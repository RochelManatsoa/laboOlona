<?php
namespace App\Entity\Enum;

enum StatusMetting: string
{
    case Planified = "PLANIFIED";
    case Canceled = "CANCELED";
    case Finished = "FINISHED";
}