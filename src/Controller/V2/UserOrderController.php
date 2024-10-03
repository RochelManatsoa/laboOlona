<?php

namespace App\Controller\V2;

use App\Data\QuerySearchData;
use App\Entity\BusinessModel\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard/user/order')]
class UserOrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ){}

    #[Route('/', name: 'app_v2_user_order')]
    public function index(): Response
    {
        return $this->render('v2/dashboard/user_order/index.html.twig', [
            'orders' => $this->em->getRepository(Order::class)->filterByUser(new QuerySearchData)
        ]);
    }

    #[Route('/show/{orderNumber}', name: 'app_v2_user_order_show')]
    public function show(Order $order): Response
    {
        return $this->render('v2/dashboard/user_order/show.html.twig', [
            'order' => $order
        ]);
    }
}
