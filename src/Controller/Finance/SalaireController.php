<?php

namespace App\Controller\Finance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SalaireController extends AbstractController
{
    #[Route('/finance/salaire', name: 'app_finance_salaire')]
    public function index(): Response
    {
        return $this->render('finance/salaire/index.html.twig', [
            'controller_name' => 'SalaireController',
        ]);
    }
}
