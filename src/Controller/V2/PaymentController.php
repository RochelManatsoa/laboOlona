<?php

namespace App\Controller\V2;

use App\Service\PaymentService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BusinessModel\TransactionType;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{

    public function __construct(
        private PaymentService $paymentService,
        private TransactionManager $transactionManager,
        private CreditManager $creditManager,
    ){}

    #[Route('/paypal/checkout/{orderNumber}', name: 'app_v2_paypal_checkout')]
    public function checkout(string $orderNumber, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderNumber]);

        $paymentData = [
            'total' => $order->getTotalAmount(),
            'currency' => 'EUR',
            'description' => 'Transaction description',
            'returnUrl' => $this->generateUrl('app_v2_paypal_payment_success', ['orderNumber' => $orderNumber], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancelUrl' => $this->generateUrl('app_v2_paypal_payment_cancel', ['orderNumber' => $orderNumber], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $response = $this->paymentService->createPayment($paymentData);
        
        $approvalUrl = null;
        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                $approvalUrl = $link->href;
                break;
            }
        }

        return $this->redirect($approvalUrl);
    }

    #[Route('/paypal/payment-success/{orderNumber}', name: 'app_v2_paypal_payment_success')]
    public function paymentSuccess(Request $request, string $orderNumber, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderNumber]);

        $payerId = $request->query->get('PayerID');
        $token = $request->query->get('token');
    
        if ($payerId && $token) {
            
            try {
                $result = $this->paymentService->executePayment($token);
    
                if ($result->status === 'COMPLETED') {
                    $capture = $result->purchase_units[0]->payments->captures[0];
                    $transaction = $order->getTransaction();
                    if(!$transaction instanceof Transaction){
                        $transaction = $this->transactionManager->init();
                    }
                    $transaction->setCommand($order);
                    $transaction->setStatus(Transaction::STATUS_AUTHORIZED);
                    $transaction->setTypeTransaction($order->getPaymentMethod());
                    $transaction->setReference($result->payer->payer_id);
                    $transaction->setPackage($order->getPackage());
                    $transaction->setAmount($order->getPackage()->getPrice());
                    $transaction->setCreditsAdded($order->getPackage()->getCredit());
                    $this->transactionManager->save($transaction);
                    $this->creditManager->notifyTransaction($transaction);
                    $this->creditManager->validateTransaction($transaction);
                    $order->setStatus(Order::STATUS_COMPLETED);
                    $order->setToken($token); 
                    $order->setPaymentId($capture->id); 
                    $order->setPayerId($result->payer->payer_id);
                    $order->setTotalAmount($capture->amount->value); 
    
                    $entityManager->persist($order);
                    $entityManager->flush();
    
                    return $this->render('v2/dashboard/payment/paypal.html.twig', [
                        'status' => 'Succès',
                        'payment' => true,
                        'order' => $order,
                    ]);
                }
    
            } catch (\Exception $ex) {
                // Gérer l'exception
                $this->addFlash('error', 'Erreur lors de la capture du paiement : '. $ex->getMessage());
            }
        }

        return $this->render('v2/dashboard/payment/paypal.html.twig', [
            'status' => 'Succès',
            'payment' => true,
            'order' => $order,
        ]);
    }

    #[Route('/paypal/payment-cancel/{orderNumber}', name: 'app_v2_paypal_payment_cancel')]
    public function paymentCancel(Request $request, string $orderNumber, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderNumber]);
        $order->setStatus(Order::STATUS_CANCELLED);  

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->render('v2/dashboard/payment/paypal.html.twig', [
            'status' => 'Echec',
            'payment' => false,
            'order' => $order,
        ]);
    }

    #[Route('/mobile-money/{orderNumber}', name: 'app_v2_mobile_money_checkout')]
    public function mobileMoney(Order $order, Request $request, TransactionManager $transactionManager): Response
    {
        $mobileMoney = $order->getPaymentMethod();
        $transaction = $order->getTransaction();
        if(!$transaction instanceof Transaction){
            $transaction = $this->transactionManager->init();
            $transaction->setCommand($order);
        }
        $transaction->setTypeTransaction($mobileMoney);
        $transaction->setCommand($order);
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $transactionManager->saveForm($form);
            
            return $this->redirectToRoute('app_v2_user_order');
        }else {
            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/turbo/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('v2/dashboard/payment/mobile.html.twig', [
            'status' => 'Succès',
            'payment' => true,
            'order' => $order,
            'form' => $form->createView(),
            'mobileMoney' => $mobileMoney,
        ]);
    }
}