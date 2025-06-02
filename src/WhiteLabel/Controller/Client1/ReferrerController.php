<?php

namespace App\WhiteLabel\Controller\Client1;

use Symfony\UX\Chartjs\Model\Chart;
use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Manager\Referrer\ReferenceManager;
use Knp\Component\Pager\PaginatorInterface;
use App\WhiteLabel\Form\Client1\ReferrerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\WhiteLabel\Service\Client1\UserService;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Entity\Client1\ReferrerProfile;
use App\WhiteLabel\Entity\Client1\Referrer\Referral;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use App\WhiteLabel\Form\Client1\Referrer\ReferralType;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\WhiteLabel\Repository\Client1\Referrer\ReferralRepository;
use App\WhiteLabel\Repository\Client1\Entreprise\JobListingRepository;

#[Route('/referrer')]
class ReferrerController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private JobListingRepository $jobListingRepository,
        private ReferralRepository $referralRepository,
        private UserService $userService,
        private EntityManagerInterface $entityManager,
        private ReferenceManager $referenceManager,
        private PaginatorInterface $paginatorInterface,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    #[Route('/', name: 'app_referrer')]
    public function index(): Response
    {
        return $this->render('white_label/client1/referrer/index.html.twig', []);
    }

    #[Route('/annonces', name: 'app_referrer_annonces')]
    public function annonces(Request $request): Response
    {
        $data = $this->jobListingRepository->findAllJobListingPublished();

        return $this->render('white_label/client1/referrer/annonces.html.twig', [
            'annonces' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/posting/{slug}', name: 'app_referrer_posting_view')]
    public function view(Request $request, JobListing $annonce): Response
    {
        $data = $this->jobListingRepository->findAllJobListingPublished();

        return $this->render('white_label/client1/referrer/view.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/cooptation/{jobId}', name: 'app_referrer_cooptation')]
    public function cooptation(Request $request, JobListing $annonce): Response
    {
        $referrer = $this->userService->getReferrer();
        $referral = (new Referral())->setReferredBy($referrer)->setStep(1)->setAnnonce($annonce)->setRewards(10);
        $form = $this->createForm(ReferralType::class, $referral);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre recommandation a bien été envoyée.');
            // $this->mailManager->cooptation($form->getData());
            return $this->redirectToRoute('app_referrer');
        }

        return $this->render('white_label/client1/referrer/cooptation/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/stats', name: 'app_referrer_stats')]
    public function stats(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $pie = $chartBuilder->createChart(Chart::TYPE_PIE);
        $data = $this->referralRepository->countReferralsByDate($this->userService->getReferrer());

        $startDate = new \DateTime('2024-02-01');
        $endDate = new \DateTime('now');
        // Initialiser toutes les dates avec 0 referrals
        while ($startDate <= $endDate) {
            $formattedResults[$startDate->format('d/m')] = 0;
            $startDate->modify('+1 day');
        }

        // Maintenant, ajoutez les données de referrals existantes
        foreach ($data as $result) {
            $dateKey = $result['date']->format('d/m');
            $formattedResults[$dateKey] += $result['referralCount']; // Cela assumera maintenant que $dateKey existe déjà
        }

        // Les labels et les valeurs peuvent être extraites directement
        $labels = array_keys($formattedResults);
        $values = array_values($formattedResults);


        // dd($formattedResults, $d, $k);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Mes recommandations',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $values,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 10,
                ],
            ],
        ]);

        $pie->setData([
            'labels' => ['Étape 1', 'Étape 2', 'Étape 3', 'Étape 4', 'Étape 5', 'Étape 6'],
            'datasets' => [
                [
                    'label' => 'Mes cooptés',
                    'backgroundColor' => ['#0d6efd', '#6c757d', '#198754', '#dc3545', '#ffc107', '#0dcaf0'],
                    'data' => [1, 5, 2, 8, 9, 2],
                ],
            ],
        ]);

        return $this->render('white_label/client1/referrer/stats.html.twig', [
            'chart' => $chart,
            'pie' => $pie,
            'referrals' => $data,
        ]);
    }

    #[Route('/rewards', name: 'app_referrer_rewards')]
    public function rewards(Request $request): Response
    {
        $referrer = $this->userService->getReferrer();
        $data = $this->referralRepository->findBy(['referredBy' => $referrer]);

        return $this->render('white_label/client1/referrer/rewards.html.twig', [
            'referrer' => $referrer,
            'referrals' => $this->paginatorInterface->paginate(
                $this->referenceManager->getReferenceAnnonce($data),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/references', name: 'app_referrer_references')]
    public function references(Request $request): Response
    {
        $referrer = $this->userService->getReferrer();
        $data = $this->referralRepository->findBy(['referredBy' => $referrer]);

        return $this->render('white_label/client1/referrer/references.html.twig', [
            'referrals' => $this->paginatorInterface->paginate(
                $this->referenceManager->getReferenceAnnonce($data),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }    

    #[Route('/info', name: 'app_referrer_info')]
    public function info(): Response
    {
        return $this->render('white_label/client1/referrer/info.html.twig', []);
    }

    #[Route('/become', name: 'app_referrer_become')]
    public function become(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $referrer = $user->getReferrerProfile();
        if (!$referrer instanceof ReferrerProfile) {
            $referrer = $this->userService->initReferrer($user);
        }
        $form = $this->createForm(ReferrerType::class, $referrer);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $referrer = $form->getData();
            $user = $referrer->getReferrer();
            $this->entityManager->persist($form->getData());
            $this->entityManager->flush();
            $this->addFlash('success', 'Vous informations sont bien ennregistrées');

            return $this->redirectToRoute('app_referrer');
        }

        return $this->render('white_label/client1/referrer/become.html.twig', [
            'form' => $form->createView()
        ]);
    }
}