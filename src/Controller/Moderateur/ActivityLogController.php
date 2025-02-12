<?php

namespace App\Controller\Moderateur;

use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\Logs\ActivityLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/moderateur/activity/log')]
class ActivityLogController extends AbstractController
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private PaginatorInterface $paginatorInterface
    ) {}

    #[Route('/page-chart', name: 'app_moderateur_activity_page_chart', methods: ['GET'])]
    public function pageChart(Request $request, ActivityLogRepository $activityLogRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $page = $request->query->get('page', 1);
        $route = $request->query->get('route', null);
        $days = $request->query->getInt('days', 0);
        $array = [
            'page' => is_numeric($page) && (int)$page > 0 ? (int)$page : 1,
            'days' => $days,
            'route' => $route,
        ];

        $logs = [];
        if($route) {
            if($route == 'app_v2_payment') {
                $logs = $activityLogRepository->findLikeLogsByUrls(['/mobile-money/_order', '/paypal/checkout/_order'], $days);
            } else {
                $logs = $activityLogRepository->findLikeLogs($route, $days);
            }
        }

        $pageViewData = $activityLogRepository->getUniqueUserPageViewsByDate($array);
        $dates = [];
        $uniqueUsers = [];
        
        foreach ($pageViewData as $data) {
            $dates[] = $data['date'];
            $uniqueUsers[] = $data['uniqueUsers'];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE, 'Page views par utilisateur');
        $chart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Utilisateurs',
                    'backgroundColor' => 'rgba(0, 99, 132, 1)',
                    'borderColor' => 'rgba(0, 0, 132, 1)',
                    'data' => $uniqueUsers,
                ],
            ],
        ]);


        return $this->render('moderateur/activity/page_chart.html.twig', [
            'chart' => $chart,
            'statuses' => [
                'Utilisateur' => null ,
                'Crédit' => 'app_v2_credit' ,
                'Paiement' => 'app_v2_payment' ,
                'Simulateur candidat' => 'app_v2_candidate_simulator_create' ,
                'Simulateur entreprise' => 'app_v2_recruiter_simulator_create' ,
                'Profils' => 'app_v2_profiles' ,
                'Prestation' => 'app_v2_prestation' ,
                'Offre d\'emploi' => 'app_v2_job_offer' ,                
            ],
            'selectedStatus' => $route,
            'selectedDays' => $days,
            'logs' => $this->paginatorInterface->paginate($logs, $page, 20)
        ]);
    }
}
