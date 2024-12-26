<?php

namespace App\Controller\Coworking;

use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Form\Coworking\OrderType;
use App\Security\Voter\OrderVoter;
use App\Entity\BusinessModel\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BusinessModel\OrderRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking/order')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(
        OrderRepository $orderRepository,
        Request $request,
        Security $security,
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = $security->isGranted(OrderVoter::LIST_ALL);
        $orders = $orderRepository->paginateRecipes($page, $canListAll ? null : $userId);
        $ordersToday = $orderRepository->findOrdersFromTodayFiveAM();
        $ordersBankedToday = $orderRepository->findOrdersFromTodayFiveAM(Order::STATUS_COMPLETED);
        $amount = 0;
        $amountTotal = 0;
        foreach ($ordersToday as $orderToday) {
            $amountTotal += ($orderToday->getTotalAmount() * $orderToday->getCurrency()->getTaux());
        }
        foreach ($ordersBankedToday as $orderBankedToday) {
            $amount += ($orderBankedToday->getTotalAmount() * $orderBankedToday->getCurrency()->getTaux());
        }

        return $this->render('coworking/order/index.html.twig', [
            'orders' => $orders,
            'ordersToday' => $ordersToday,
            'ordersBankedToday' => $ordersBankedToday,
            'amount' => $amount,
            'amountTotal' => $amountTotal,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $devise = $entityManager->getRepository(Devise::class)->findOneBy(['slug' => 'ariary']);
        $order->setCurrency($devise);
        $order->setPaymentId('COWORKING');
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderItems = $form->get('orderItems')->getData();
            $totalAmount = 0;
            foreach ($orderItems as $orderItem) {
                $totalAmount += ($orderItem->getPrice() * $orderItem->getQuantity());
            }
            $order->setTotalAmount($totalAmount);
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    #[IsGranted('ORDER_EDIT', subject: 'order')]
    public function show(Order $order): Response
    {
        return $this->render('coworking/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderItems = $form->get('orderItems')->getData();
            $totalAmount = 0;
            foreach ($orderItems as $orderItem) {
                $totalAmount += ($orderItem->getPrice() * $orderItem->getQuantity());
            }
            $order->setTotalAmount($totalAmount);
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
