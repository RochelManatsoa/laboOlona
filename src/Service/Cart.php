<?php
namespace App\Service;

use App\Repository\Coworking\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class Cart
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ){}

    public function addProduct(int $productId): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        $cart[$productId] = ($cart[$productId] ?? 0) + 1;
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function removeProduct(int $productId): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function getCart(): array
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $this->productRepository->find($id),
                'quantity' => $quantity
            ];
        }

        return $cartWithData;
    }

    public function getDefaultCart(): array
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $id,
                'quantity' => $quantity
            ];
        }

        return $cartWithData;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }
        return $total;
    }
}