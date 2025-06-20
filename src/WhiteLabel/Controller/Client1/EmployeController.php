<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
#[Route('/employe')]
class EmployeController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    #[Route('/', name: 'app_white_label_client1_employe')]
    public function index(): Response
    {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();

        $referrer = $user?->getReferrerProfile();
        $employe = $user?->getEmploye();

        $referralCount = $referrer ? $referrer->getReferrals()->count() : 0;
        $totalRewards = $referrer ? $referrer->getTotalRewards() : 0;
        $pendingRewards = $referrer ? $referrer->getPendingRewards() : 0;

        return $this->render('white_label/client1/employe/index.html.twig', [
            'referralCount' => $referralCount,
            'totalRewards' => $totalRewards,
            'pendingRewards' => $pendingRewards,
            'employe' => $employe,
            'referrer' => $referrer,
        ]);
    }

    #[Route('/mes-infos', name: 'app_white_label_client1_employe_profile')]
    public function profile(): Response
    {
        return $this->render('white_label/client1/employe/profile.html.twig');
    }
}
