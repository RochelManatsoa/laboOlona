<?php

namespace App\Controller\Finance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/finance/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_finance_dashboard')]
    public function index(): Response
    {
        return $this->render('finance/dashboard/index.html.twig', []);
    }
}
