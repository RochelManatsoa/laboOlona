<?php

namespace App\Controller\Dashboard\Referrer;

use App\Entity\Entreprise\JobListing;
use App\Entity\Referrer\Referral;
use App\Form\Referrer\ReferralType;
use App\Manager\MailManager;
use App\Manager\Referrer\ReferenceManager;
use App\Repository\Referrer\ReferralRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/dashboard/referrer/cooptation')]
class CooptationController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailManager $mailManager,
        private ReferenceManager $referenceManager,
        private ReferralRepository $referralRepository,
        private PaginatorInterface $paginatorInterface,
    ) {}
    
    #[Route('/{jobId}', name: 'app_dashboard_referrer_cooptation')]
    public function index(Request $request, JobListing $annonce): Response
    {
        $referrer = $this->userService->getReferrer();
        $referral = (new Referral())->setReferredBy($referrer)->setStep(1)->setAnnonce($annonce)->setRewards($annonce->getSalaire() * 0.1);
        $form = $this->createForm(ReferralType::class, $referral);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Votre recommandation a bien été envoyée.');
            $this->mailManager->cooptation($form->getData());
            return $this->redirectToRoute('app_dashboard_referrer');
        }

        return $this->render('dashboard/referrer/cooptation/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{jobId}/references', name: 'app_dashboard_referrer_cooptation_references')]
    public function references(Request $request, JobListing $annonce): Response
    {
        $referrer = $this->userService->getReferrer();
        $data = $this->referralRepository->findBy(['referredBy' => $referrer, 'annonce' => $annonce]);
        return $this->render('dashboard/referrer/cooptation/references.html.twig', [
            'referrals' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'annonce' => $annonce
        ]);
    }
}
