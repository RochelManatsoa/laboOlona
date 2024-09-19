<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('scroll_candidat_component')]
class ScrollCandidatsComponent
{
    public array $candidats;
}