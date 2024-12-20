<?php

namespace App\Controller\Coworking;

use App\Entity\User;
use App\Service\Cart;
use App\Form\Coworking\OrderType;
use App\Entity\BusinessModel\Order;
use App\Entity\Coworking\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\Coworking\ProductRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index', methods: ['GET', 'POST'])]
    public function index(
        Cart $cartService
    )
    {                
        return $this->render('coworking/cart/index.html.twig', [
            'items' => $cartService->getCart(),
            'jsonItems' => json_encode($cartService->getDefaultCart()),
            'total' => $cartService->getTotal()
        ]);
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        Cart $cartService
    )
    {                
        $userId = $request->request->get('user');
        $user = $entityManager->getRepository(User::class)->find($userId);
        $items = json_decode($request->request->get('items'), true);
        $total = $request->request->get('total');

        $order = new Order();
        $order->setCustomer($user);
        $order->setTotalAmount($total);
        $order->setStatus(Order::STATUS_PENDING);
        $order->setCreatedAt(new \DateTime());
        $order->setUpdatedAt(new \DateTime());

        foreach ($items as $item) {
            $product = $productRepository->find($item['product']);
            $orderItem = new OrderItem($product, $item['quantity']);
            $orderItem->setCommand($order);
            $orderItem->setPrice($product->getPrice() * $item['quantity']);
            $entityManager->persist($orderItem);
            $cartService->removeProduct($orderItem->getProduct()->getId());
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Commande enregistrée avec succès');

        return $this->redirectToRoute('app_order_index');
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
