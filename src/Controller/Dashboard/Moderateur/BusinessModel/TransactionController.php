<?php

namespace App\Controller\Dashboard\Moderateur\BusinessModel;

use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Data\BusinessModel\TransactionData;
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
        if($form->isSubmitted() && $form->isValid()){
            $submittedToken = $form->get('token')->getData();
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('transaction'.$transaction->getId(), $submittedToken))) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
            $this->transactionManager->saveForm($form);
            if($this->creditManager->validateTransaction($form->getData())){
                $this->addFlash('success', 'Transaction mis à jour');
            }else{
                $this->addFlash('danger', 'Une erreur s\'est produite lors de la mis à jour');
            }

            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_business_model_transaction');
        }
        
        return $this->render('dashboard/moderateur/business_model/transaction/view.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{transaction}', name: 'app_dashboard_moderateur_business_model_transaction_delete')]
    public function delete(Request $request, Transaction $transaction): Response
    {
        $transactionId = $transaction->getId();
        $message = "La transaction a bien été supprimée";
        $this->em->remove($transaction);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/transaction/delete.html.twig', [
                'transactionId' => $transactionId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_prestation');
    }
}
