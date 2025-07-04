<?php

namespace App\Controller\V2;

use App\Controller\TableauDeBord\CandidatController;
use App\Controller\TableauDeBord\EntrepriseController;
use App\Entity\User;
use App\Service\ActivityLogger;
use App\Service\PaymentService;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Order;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
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
        private MailerService $mailerService,
        private UserService $userService,
        private CandidatController $candidatController,
        private EntrepriseController $entrepriseController,
        private ActivityLogger $activityLogger
    ){}

    #[Route('/paypal/checkout/{orderNumber}', name: 'app_v2_paypal_checkout')]
    public function checkout(string $orderNumber, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->userService->getCurrentUser();
        if($currentUser instanceof User) {
            $this->activityLogger->logPageViewActivity($currentUser, '/paypal/checkout/_order');
        }
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
        $profile = $this->userService->checkUserProfile($order->getCustomer());
    
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
                    $transaction->setStatus(Transaction::STATUS_COMPLETED);
                    $transaction->setTypeTransaction($order->getPaymentMethod());
                    $transaction->setReference($result->payer->payer_id);
                    $transaction->setPackage($order->getPackage());
                    $transaction->setAmount($order->getPackage()->getPrice());
                    $transaction->setCreditsAdded($order->getPackage()->getCredit());
                    $this->transactionManager->save($transaction);
                    if($order->getPackage()->getType() === 'ABONNEMENT'){
                        if($profile instanceof CandidateProfile || $profile instanceof EntrepriseProfile){
                            $profile->setIsPremium(true);
                            $entityManager->persist($profile);
                            $entityManager->flush();
                        }
                    }
                    $this->creditManager->notifyTransaction($transaction);
                    $this->creditManager->validateTransaction($transaction, $transaction->getTypeTransaction()->getName());
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

        if($profile instanceof CandidateProfile){
            $params = $this->candidatController->getData();
            $params['status'] = 'Succès';
            $params['payment'] = true;
            $params['order'] = $order;
            return $this->render('tableau_de_bord/candidat/paypal.html.twig', $params);
        }

        if($profile instanceof EntrepriseProfile){
            $params = $this->entrepriseController->getData();
            $params['status'] = 'Succès';
            $params['payment'] = true;
            $params['order'] = $order;
            return $this->render('tableau_de_bord/entreprise/paypal.html.twig', $params);
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
        $profile = $this->userService->checkUserProfile($order->getCustomer());

        $entityManager->persist($order);
        $entityManager->flush();

        if($profile instanceof CandidateProfile){
            $params = $this->candidatController->getData();
            $params['status'] = 'Succès';
            $params['payment'] = false;
            $params['order'] = $order;
            return $this->render('tableau_de_bord/candidat/paypal.html.twig', $params);
        }

        if($profile instanceof EntrepriseProfile){
            $params = $this->entrepriseController->getData();
            $params['status'] = 'Succès';
            $params['payment'] = false;
            $params['order'] = $order;
            return $this->render('tableau_de_bord/entreprise/paypal.html.twig', $params);
        }

        return $this->render('v2/dashboard/payment/paypal.html.twig', [
            'status' => 'Echec',
            'payment' => false,
            'order' => $order,
        ]);
    }

    #[Route('/mobile-money/{orderNumber}', name: 'app_v2_mobile_money_checkout')]
    public function mobileMoney(Order $order, Request $request, TransactionManager $transactionManager): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
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
        $this->activityLogger->logPageViewActivity($currentUser, '/mobile-money/_order');
        
        if ($form->isSubmitted() && $form->isValid()) {
            $transaction = $form->getData();
            $command = $form->getData()->getCommand();
            $command->setStatus(Order::STATUS_PROCESSING);
            $transaction->setPackage($command->getPackage());
            $transaction->setUpdatedAt(new \DateTime());
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transactionManager->save($transaction);

            /** On envoi un mail */
            $this->mailerService->sendMultiple(
                ["contact@olona-talents.com", "admin@olona-talents.com", "aolonaprodadmi@gmail.com", "partenaires@olona-talents.com"],
                "Paiement sur Olona Talents",
                "notification_paiement.html.twig",
                [
                    'user' => $currentUser,
                    'transaction' => $transaction,
                    'order' => $order,
                    'dashboard_url' => $this->generateUrl('app_dashboard_moderateur_business_model_transaction_view', [
                        'transaction' => $transaction->getId(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            
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