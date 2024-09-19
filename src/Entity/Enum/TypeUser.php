<?php
namespace App\Entity\Enum;

enum TypeUser: string
{
    case Candidat = "CANDIDAT";
    case Entreprise = "ENTREPRISE";
    case Moderateur = "MODERATEUR";
}