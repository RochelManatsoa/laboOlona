<?php

namespace App\Controller\V2;

use App\Entity\BusinessModel\Package;
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

#[Route('/v2/dashboard/credit')]
class CreditController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PackageRepository $packageRepository,
        private TransactionManager $transactionManager,
    ){}
    
    #[Route('/', name: 'app_v2_credit')]
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $transaction = $this->transactionManager->init();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->transactionManager->saveForm($form);
                $response = [
                    'message' => 'Votre commande a été bien enregistré',
                    'success' => true,
                    'status' => 'Succès',
                    'credit' => $currentUser->getCredit()->getTotal(),
                ];
    
                if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                    return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
                }
            } else {

                if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                    return $this->render('v2/turbo/form_errors.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }
        }

        return $this->render('v2/dashboard/credit/index.html.twig', [
            'packages' => $this->packageRepository->findBy([], ['id' => 'DESC']),
            'form' => $form->createView()
        ]);
    }
    
    #[Route('/{slug}', name: 'app_v2_credit_view')]
    public function pack(Request $request, Package $package): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $transaction = $this->transactionManager->init();
        $transaction->setPackage($package);
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->transactionManager->saveForm($form);
            $response = [
                'message' => 'Votre commande a été bien enregistré',
                'success' => true,
                'status' => 'Succès',
                'credit' => $currentUser->getCredit()->getTotal(),
            ];

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
            }
        } else {

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/turbo/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('v2/dashboard/credit/view.html.twig', [
            'packages' => $this->packageRepository->findBy([], ['id' => 'DESC']),
            'form' => $form->createView()
        ]);
    }
}
