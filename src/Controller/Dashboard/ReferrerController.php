<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Entity\User;
use App\Entity\ReferrerProfile;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\UX\Chartjs\Model\Chart;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\Referrer\ReferenceManager;
use App\Form\Profile\Referrer\ReferrerType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\Referrer\ReferralRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/referrer')]
class ReferrerController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private ReferralRepository $referralRepository,
        private UserService $userService,
        private EntityManagerInterface $em,
        private ReferenceManager $referenceManager,
        private ProfileManager $profileManager,
        private PaginatorInterface $paginatorInterface,
    ) {}

    #[Route('/', name: 'app_dashboard_referrer')]
    public function index(): Response
    {
        return $this->render('dashboard/referrer/index.html.twig', []);
    }

    #[Route('/annonces', name: 'app_dashboard_referrer_annonces')]
    public function annonces(Request $request): Response
    {
        $data = $this->jobListingRepository->findAllJobListingPublished();

        return $this->render('dashboard/referrer/annonces.html.twig', [
            'annonces' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/posting/{slug}', name: 'app_dashboard_referrer_posting_view')]
    public function view(Request $request, JobListing $annonce): Response
    {
        $data = $this->jobListingRepository->findAllJobListingPublished();

        return $this->render('dashboard/referrer/view.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/stats', name: 'app_dashboard_referrer_stats')]
    public function stats(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $pie = $chartBuilder->createChart(Chart::TYPE_PIE);
        $data = $this->referralRepository->countReferralsByDate($this->userService->getReferrer());

        $startDate = new DateTime('2024-02-01');
        $endDate = new DateTime('now');
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

        return $this->render('dashboard/referrer/stats.html.twig', [
            'chart' => $chart,
            'pie' => $pie,
            'referrals' => $data,
        ]);
    }

    #[Route('/rewards', name: 'app_dashboard_referrer_rewards')]
    public function rewards(Request $request): Response
    {
        $referrer = $this->userService->getReferrer();
        $data = $this->referralRepository->findBy(['referredBy' => $referrer]);

        return $this->render('dashboard/referrer/rewards.html.twig', [
            'referrer' => $referrer,
            'referrals' => $this->paginatorInterface->paginate(
                $this->referenceManager->getReferenceAnnonce($data),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/references', name: 'app_dashboard_referrer_references')]
    public function references(Request $request): Response
    {
        $referrer = $this->userService->getReferrer();
        $data = $this->referralRepository->findBy(['referredBy' => $referrer]);

        return $this->render('dashboard/referrer/references.html.twig', [
            'referrals' => $this->paginatorInterface->paginate(
                $this->referenceManager->getReferenceAnnonce($data),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }    

    #[Route('/info', name: 'app_dashboard_referrer_info')]
    public function info(): Response
    {
        return $this->render('dashboard/referrer/info.html.twig', []);
    }

    #[Route('/become', name: 'app_dashboard_referrer_become')]
    public function become(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $referrer = $user->getReferrerProfile();
        if (!$referrer instanceof ReferrerProfile) {
            $referrer = $this->profileManager->createReferrer($user);
        }
        $form = $this->createForm(ReferrerType::class, $referrer);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $referrer = $form->getData();
            $user = $referrer->getReferrer();
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Vous informations sont bien ennregistrées');

            return $this->redirectToRoute('app_dashboard_referrer');
        }

        return $this->render('dashboard/referrer/become.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
