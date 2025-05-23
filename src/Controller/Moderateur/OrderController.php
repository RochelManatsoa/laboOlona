<?php

namespace App\Controller\Moderateur;

use App\Entity\BusinessModel\Order;
use App\Form\BusinessModel\OrderForm;
use App\Repository\BusinessModel\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderateur/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_moderateur_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $userId = $request->query->get('userId', null);
        return $this->render('moderateur/order/index.html.twig', [
            'orders' => $orderRepository->paginateRecipes($page, $userId),
        ]);
    }

    #[Route('/new', name: 'app_moderateur_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderForm::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('moderateur/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderForm::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
