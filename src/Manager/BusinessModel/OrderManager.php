<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function init(): Order
    {
        $order = new Order();
        $order->setCustomer($this->security->getUser());

        return $order;
    }

    public function save(Order $order)
    {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Order $order */
        $order = $form->getData();
        $this->save($order);

        return $order;
    }
}