<?php

namespace App\Controller\Coworking;

use App\Service\Cart;
use App\Entity\BusinessModel\Order;
use App\Entity\Coworking\OrderItem;
use App\Form\Coworking\OrderType;
use App\Repository\Coworking\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        Cart $cartService
    )
    {                
        return $this->render('coworking/cart/index.html.twig', [
            'items' => $cartService->getCart(),
            'total' => $cartService->getTotal()
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add($id, Cart $cartService)
    {
        $cartService->addProduct($id);
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove($id, Cart $cartService)
    {
        $cartService->removeProduct($id);
        return $this->redirectToRoute('app_cart_index');
    }
}
