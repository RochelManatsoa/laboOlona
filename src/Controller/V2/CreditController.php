<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Package;
use App\Form\BusinessModel\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\BusinessModel\TransactionType;
use App\Manager\BusinessModel\OrderManager;
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
        private OrderManager $orderManager,
    ){}
    
    #[Route('/', name: 'app_v2_credit')]
    public function index(Request $request): Response
    {
        // $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        // $routeInfo['params'] = [];
        
        // return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var Devise $currency */
        $currency = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $transaction = $this->transactionManager->init();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->transactionManager->saveForm($form);
            $response = [
                'message' => 'Votre commande a été bien enregistré',
                'success' => true,
                'status' => '<i class="bi bi-check-lg me-2"></i> Succès',
                'credit' => $currentUser->getCredit()->getTotal(),
            ];

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
            }
        }else {
            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/turbo/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('v2/dashboard/credit/index.html.twig', [
            'packages' => $this->packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']),
            'form' => $form->createView(),
            'currency' => $currency,
        ]);
    }
    
    #[Route('/{slug}', name: 'app_v2_credit_view')]
    public function pack(Request $request, Package $package): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = ['slug' => $package->getSlug()];
        
        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        /** @var Devise $currency */
        $currency = $this->em->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        $order = $this->orderManager->init();
        $order->setPackage($package);
        $order->setCurrency($currency);
        $order->setTotalAmount($this->convertToEuro($package->getPrice(), $currency));
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $order = $this->orderManager->saveForm($form);
            
            return $this->redirectToRoute('app_v2_mobile_money_checkout', [
                'orderNumber' => $order->getOrderNumber()
            ]);
        } 

        return $this->render('v2/dashboard/credit/recap.html.twig', [
            'packages' => $this->packageRepository->findBy(['type' => 'CREDIT'], ['id' => 'DESC']),
            'form' => $form->createView(),
            'pack' => $package,
            'price' => $this->convertToEuro($package->getPrice(), $currency),
        ]);
    }

    private function convertToEuro(float $price, Devise $currency): float 
    {
        return number_format($price / $currency->getTaux(), 2, '.', '');
    }
}
