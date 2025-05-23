<?php
namespace App\Entity\Enum;

enum TypeContrat: string
{
    case CDI = "CDI";
    case CDD = "CDD";
    case Stage = "STAGE";
    case Alternance = "ALTERNANCE";
}