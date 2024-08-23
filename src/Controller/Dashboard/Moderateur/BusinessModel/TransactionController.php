<?php

namespace App\Controller\Dashboard\Moderateur\BusinessModel;

use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\BusinessModel\TransactionStaffType;
use App\Manager\BusinessModel\TransactionManager;
use Google\Service\AnalyticsReporting\TransactionData;
use App\Repository\BusinessModel\TransactionRepository;
use App\Form\Moderateur\BusinessModel\TransactionSearchFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/business-model/transaction')]
class TransactionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private TransactionRepository $transactionRepository,
        private TransactionManager $transactionManager,
    ){}

    #[Route('/', name: 'app_dashboard_moderateur_business_model_transaction')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new TransactionData();
        $data->page = $request->get('page', 1);
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
        $form = $this->createForm(TransactionStaffType::class, $transaction);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            dd($form->getData());
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
