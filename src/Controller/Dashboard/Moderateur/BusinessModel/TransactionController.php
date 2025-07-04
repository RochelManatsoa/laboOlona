<?php

namespace App\Controller\Dashboard\Moderateur\BusinessModel;

use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Data\BusinessModel\TransactionData;
use App\Entity\BusinessModel\Order;
use App\Form\BusinessModel\TransactionAdminForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\BusinessModel\TransactionStaffType;
use App\Manager\BusinessModel\TransactionManager;
use App\Repository\BusinessModel\TransactionRepository;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Form\Moderateur\BusinessModel\TransactionSearchFormType;
use App\Manager\BusinessModel\CreditManager;
use App\Manager\BusinessModel\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

#[Route('/dashboard/moderateur/business-model/transaction')]
class TransactionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private TransactionRepository $transactionRepository,
        private TransactionManager $transactionManager,
        private OrderManager $orderManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CreditManager $creditManager,
    ){}

    #[Route('/', name: 'app_dashboard_moderateur_business_model_transaction')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new TransactionData();
        $data->page = $request->get('page', 1);
        $data->status = $request->query->get('status');
        $form = $this->createForm(TransactionSearchFormType::class, $data);
        $form->handleRequest($request);
        $transactions = $this->transactionRepository->findSearch($data);

        return $this->render('dashboard/moderateur/business_model/transaction/index.html.twig', [
            'transactions' => $transactions,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{transaction}', name: 'app_dashboard_moderateur_business_model_transaction_view')]
    public function view(Request $request, Transaction $transaction): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $transactionToken = $this->csrfTokenManager->getToken('transaction'.$transaction->getId())->getValue();
        $transaction->setToken($transactionToken);
        $form = $this->createForm(TransactionStaffType::class, $transaction);
        $form->handleRequest($request);
        $total = $transaction->getAmount();
        foreach($transaction->getTransactionReferences() as $value){
            $total = $total + $value->getMontant();
        }
        if($form->isSubmitted() && $form->isValid()){
            $submittedToken = $form->get('token')->getData();
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('transaction'.$transaction->getId(), $submittedToken))) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
            $transaction = $this->transactionManager->saveForm($form);
            $order = $transaction->getCommand();
            if(!$order instanceof Order){
                $order = new Order();
                $order->setTransaction($transaction);
            }
            $order->setStatus($transaction->getStatus());
            $order->setPaymentId($transaction->getReference()); 
            $order->setPayerId($transaction->getUser()->getId());
            $order->setTotalAmount($transaction->getPackage()->getPrice()); 
            $this->orderManager->save($order);
            $this->creditManager->notifyTransaction($transaction);
            if($transaction->getStatus() === Transaction::STATUS_AUTHORIZED || $transaction->getStatus() === Transaction::STATUS_COMPLETED){
                $this->creditManager->validateTransaction($transaction, $transaction->getTypeTransaction()->getName());
            }
            $this->addFlash('success', 'Transaction mis à jour');

            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_business_model_transaction');
        }
        
        return $this->render('dashboard/moderateur/business_model/transaction/view.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'app_dashboard_moderateur_business_model_transaction_new')]
    public function new(Request $request, TransactionManager $transactionManager): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $transaction = $transactionManager->init();
        $form = $this->createForm(TransactionAdminForm::class, $transaction);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $transaction = $this->transactionManager->saveForm($form);
            $order = $transaction->getCommand();
            if(!$order instanceof Order){
                $order = new Order();
                $order->setTransaction($transaction);
                $order->setCustomer($transaction->getUser());
                $order->setPaymentMethod($transaction->getTypeTransaction());
                $order->setPackage($transaction->getPackage());
            }
            $order->setStatus($transaction->getStatus());
            $order->setPaymentId($transaction->getReference()); 
            $order->setPayerId($transaction->getUser()->getId());
            $order->setTotalAmount($transaction->getPackage()->getPrice()); 
            $this->orderManager->save($order);
            $this->creditManager->notifyTransaction($transaction);
            $this->addFlash('success', 'Transaction créée');

            return $this->redirectToRoute('app_dashboard_moderateur_business_model_transaction');
        }
        
        return $this->render('dashboard/moderateur/business_model/transaction/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/transaction', name: 'app_dashboard_moderateur_business_model_transaction_delete', methods:['POST'])]
    public function delete(Request $request): Response
    {
        $transactionId = $request->request->get('transactionId');
        $transaction = $this->em->getRepository(Transaction::class)->find($transactionId);
        $message = "La transaction a bien été supprimée";
        $this->em->remove($transaction);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('dashboard/moderateur/business_model/transaction/delete.html.twig', [
                'transactionId' => $transactionId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_dashboard_moderateur_business_model_transaction');
    }
}
