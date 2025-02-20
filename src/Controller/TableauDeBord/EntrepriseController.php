<?php

namespace App\Controller\TableauDeBord;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tableau-de-bord/entreprise')]

class EntrepriseController extends AbstractController
{
    #[Route('/', name: 'app_tableau_de_bord_entreprise')]
    public function index(): Response
    {
        return $this->render('tableau_de_bord/entreprise/index.html.twig', [
            
        ]);
    }
}
