<?php

namespace App\Controller\V2\Recruiter;

use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BusinessModel\TransactionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use App\Repository\BusinessModel\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/credit')]
class CreditController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PackageRepository $packageRepository,
        private TransactionManager $transactionManager,
    ){}

    #[Route('/', name: 'app_v2_recruiter_credit')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $transaction = $this->transactionManager->init();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->transactionManager->saveForm($form);        
            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/recruiter/live.html.twig', [
                    'message' => 'Votre commande a été bien enregistré',
                    'success' => true,
                    'status' => 'Succès',
                    'credit' => $currentUser->getCredit()->getTotal(),
                ]);
            }
        }

        return $this->render('v2/dashboard/recruiter/credit/index.html.twig', [
            'packages' => $this->packageRepository->findBy([], ['id' => 'DESC']),
            'form' => $form->createView()
        ]);
    }
}
