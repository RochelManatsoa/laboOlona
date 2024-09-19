<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use DateTime;
use App\Twig\AppExtension;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use Symfony\UX\Chartjs\Model\Chart;
use App\Data\Profile\StatSearchData;
use App\Manager\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\Profile\StatSearchFormType;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/stat')]
class StatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PaginatorInterface $paginatorInterface,
        private NotificationManager $notificationManager,
        private AppExtension $appExtension,
        private ModerateurManager $moderateurManager,
        private NotificationRepository $notificationRepository,
        private UserService $userService,
    ){}
    
    #[Route('/', name: 'app_dashboard_moderateur_profile_stat')]
    public function index(Request $request, ChartBuilderInterface $chartBuilderInterface): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new StatSearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(StatSearchFormType::class, $data);
        $form->handleRequest($request);

        
        // Déterminer les dates de début et de fin
        $start = $data->start;
        $end = $data->end;

        if ($start === null || $end === null) {
            $end = new DateTime(); // Aujourd'hui
            if ($start === null) {
                switch ($data->from) {
                    case 1: // Aujourd'hui
                        $start = new DateTime();
                        break;
                    case 2: // Hier
                        $start = (new DateTime())->modify('-1 day');
                        break;
                    case 3: // Avant-hier
                        $start = (new DateTime())->modify('-2 days');
                        break;
                    case 7: // 7 jours
                        $start = (new DateTime())->modify('-7 days');
                        break;
                    case 30: // 30 jours
                        $start = (new DateTime())->modify('-30 days');
                        break;
                    default: // Par défaut, depuis la première notification
                        $firstNotification = $this->notificationRepository->findOneBy([], ['dateMessage' => 'ASC']);
                        if ($firstNotification !== null) {
                            $start = $firstNotification->getDateMessage();
                        } else {
                            $start = new DateTime(); // Si aucune notification n'est trouvée, utiliser aujourd'hui comme date de début
                        }
                }
            }
        }
        // Récupérer les notifications filtrées
        $notifications = $this->notificationRepository->findSearch($data);

        // Générer la liste complète des dates entre `start` et `end`
        $labels = $this->generateDateRange($start, $end);

        
        // Agréger les notifications par date
        $dataByDate = array_fill_keys($labels, 0);

        foreach ($notifications as $notification) {
            $date = $notification->getDateMessage()->format('Y-m-d');
            if (isset($dataByDate[$date])) {
                $dataByDate[$date]++;
            } else {
                $dataByDate[$date] = 1; // Initialiser à 1 si la date n'existe pas encore
            }
        }

        // Préparer les données pour le graphique
        $dataChart = array_values($dataByDate);

        $chart = $chartBuilderInterface->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de relances',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'data' => $dataChart,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ]);

        return $this->render('dashboard/moderateur/profile/stat/index.html.twig', [
            'form' => $form->createView(),
            'chart' => $chart,
            'notifications' => $notifications,
        ]);
    }

    private function generateDateRange(\DateTime $start, \DateTime $end): array
    {
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            $end->modify('+1 day')
        );

        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}
