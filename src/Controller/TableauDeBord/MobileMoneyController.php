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

#[Route('/mobile-payment')]

class MobileMoneyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private AirtelMoneyService $airtelMoneyService,
    ){}

    #[Route('/airtel-money', name: 'app_mobile_money_airtel')]
    public function airtel(
        Request $request
    ): Response
    {
        // dd($this->airtelMoneyService->checkBalance());
        $payload = json_encode([
            "reference" => "Testing transaction",
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => "332046888"
            ],
            "transaction" => [
                "amount" => "100",
                "country" => "MG",
                "currency" => "MGA",
                "id" => "test_id"
            ]
        ]);
        dd($this->airtelMoneyService->generateSignatureAndKey($payload));
        dd($this->airtelMoneyService->payments($payload));

        return $this->render('tableau_de_bord/candidat/index.html.twig', []);
    }

}
