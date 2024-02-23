<?php

namespace App\Controller\Finance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContratController extends AbstractController
{
    #[Route('/finance/contrat', name: 'app_finance_contrat')]
    public function index(): Response
    {
        return $this->render('finance/contrat/index.html.twig', [
            'controller_name' => 'ContratController',
        ]);
    }
}
