<?php

namespace App\Controller\TableauDeBord;

use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MobileMoney\AirtelMoneyService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/dashboard')]

class RedirectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private AirtelMoneyService $airtelMoneyService,
    ){}

    #[Route('/entreprise', name: 'app_dashboard_entreprise_candidatures')]
    public function candidatures(): RedirectResponse
    {
        return $this->redirectToRoute('app_tableau_de_bord_entreprise_listes_candidatures');
    }

}
