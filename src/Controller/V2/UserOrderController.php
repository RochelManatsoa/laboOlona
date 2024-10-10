<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Data\QuerySearchData;
use App\Entity\Finance\Devise;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Manager\BusinessModel\OrderManager;
use App\Manager\BusinessModel\CreditManager;
use App\Entity\BusinessModel\TypeTransaction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/v2/dashboard/user/order')]
class UserOrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderManager $orderManager,
        private TransactionManager $transactionManager,
        private CreditManager $creditManager,
        private UrlGeneratorInterface $urlGeneratorInterface,
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

    #[Route('/api/save-transaction/{pack}/{id}', name: 'app_v2_user_order_save')]
    public function saveTransaction(Request $request, string $pack, int $id, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent(), true);
        $package = $this->em->getRepository(Package::class)->findOneBy([
            'slug' => $pack
        ]);
        $user = $this->em->getRepository(User::class)->find($id);
        $currency = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $paymentMethod = $this->em->getRepository(TypeTransaction::class)->findOneBy([
            'slug' => 'paypal'
        ]);
        /** @var Order $order */
        $order = new Order();
        $order->setCustomer($user);
        $order->setPackage($package);
        $order->setCurrency($currency);
        if ($data['status'] === 'COMPLETED') {
            $captures = $data['purchase_units'][0]['payments']['captures'];
            $order->setTotalAmount((float)$captures[0]['amount']['value']);
            $order->setPaymentId($captures[0]['id']);
            $order->setPayerId($data['payer']['payer_id']);
            $order->setPaymentMethod($paymentMethod);
            $transaction = $order->getTransaction();
            if(!$transaction instanceof Transaction){
                $transaction = $this->transactionManager->init();
            }
            $transaction->setCommand($order);
            $transaction->setStatus(Transaction::STATUS_AUTHORIZED);
            $transaction->setTypeTransaction($paymentMethod);
            $transaction->setReference($data['payer']['payer_id']);
            $transaction->setPackage($package);
            $transaction->setAmount($package->getPrice());
            $transaction->setCreditsAdded($package->getCredit());
            $this->transactionManager->save($transaction);
            $this->transactionManager->createInvoice($transaction);
            $this->creditManager->notifyTransaction($transaction);
            $this->creditManager->validateTransaction($transaction);
            $order->setStatus(Order::STATUS_COMPLETED);
        }
        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json(['status' => 'Transaction recorded'], 200);
    }

    #[Route('/payment/{order}/facture', name: 'payment_facture')]
    public function facture(Order $order, OrderManager $orderManager)
    {
        $file = $orderManager->generateFacture($order);

        return new BinaryFileResponse($file);
    }

    #[Route('/view/{order}/facture', name: 'payment_facture_view')]
    public function view(Order $order, OrderManager $orderManager)
    {
        return $this->render("v2/dashboard/payment/facture.pdf.twig", [
            'commande' => $order,
            'pathToWeb' => $this->urlGeneratorInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }
}
