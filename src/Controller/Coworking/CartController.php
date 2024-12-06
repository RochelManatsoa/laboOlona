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
        $order = new Order();
        $order->setTotalAmount($cartService->getTotal());
        $order->setCustomer($this->getUser());
        $order->setDescription('Commande de ' . $this->getUser()->getNom().' '.$this->getUser()->getPrenom().' pour '.$cartService->getTotal().' Ar');
        foreach ($cartService->getCart() as $item) {
            $p = $productRepository->find($item['product']);
            $orderItem = new OrderItem($p, $item['quantity']);
            $order->addOrderItem($orderItem);
        }
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();
            foreach ($order->getOrderItems() as $orderItem) {
                $cartService->removeProduct($orderItem->getProduct()->getId());
            }
            $this->addFlash('success', 'Commande créée avec succès');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }
        
        return $this->render('coworking/cart/index.html.twig', [
            'form' => $form->createView(),
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
