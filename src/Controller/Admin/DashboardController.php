<?php

namespace App\Controller\Admin;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\BusinessModel\Package;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Entity\User;
use App\Entity\Secteur;
use App\Entity\Finance\Devise;
use App\Entity\Errors\ErrorLog;
use App\Entity\ReferrerProfile;
use App\Entity\CandidateProfile;
use App\Entity\Cron\CronLog;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Employe;
use App\Entity\Finance\Simulateur;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use Symfony\UX\Chartjs\Model\Chart;
use App\Entity\Moderateur\Invitation;
use App\Entity\Moderateur\Assignation;
use App\Entity\Moderateur\TypeContrat;
use App\Entity\Prestation;
use App\Entity\Prestation\TypePrestation;
use App\Entity\TemplateEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ChartBuilderInterface $chartBuilder,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $userTypesCounts = $this->em->getRepository(User::class)->countUsersByType();
        foreach ($userTypesCounts as $key => $value) {
            if ($value['userType'] === null) {
                $userTypesCounts[$key]['userType'] = "NON DÉFINI";  
            }
        }
        $labels = [];
        $data = [];
        $backgroundColors = ['rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(255, 205, 86)', 'rgb(75, 192, 192)'];

        foreach ($userTypesCounts as $typeCount) {
            $labels[] = sprintf('%s (%d)', $typeCount['userType'], $typeCount['userCount']);
            $data[] = $typeCount['userCount'];
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Types d\'inscriptions',
                    'backgroundColor' => $backgroundColors,
                    'data' => $data,
                ],
            ],
        ]);
        $chartToday = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $userCountsTodayByType = $this->em->getRepository(User::class)->countUsersRegisteredTodayByType();
        foreach ($userCountsTodayByType as $key => $value) {
            if ($value['userType'] === null) {
                $userCountsTodayByType[$key]['userType'] = "NON DÉFINI";  
            }
        }
        $labelsToday = [];
        $dataToday = [];
        $backgroundColors = ['rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(255, 206, 86)', 'rgb(75, 192, 192)'];

        foreach ($userCountsTodayByType as $count) {
            $labelsToday[] = sprintf('%s (%d)', $count['userType'], $count['userCount']);
            $dataToday[] = $count['userCount'];
        }

        $chartToday->setData([
            'labels' => $labelsToday,
            'datasets' => [
                [
                    'label' => 'Inscriptions d\'aujourd\'hui par type',
                    'backgroundColor' => $backgroundColors,
                    'data' => $dataToday,
                ],
            ],
        ]);

        return $this->render('admin/index.html.twig', [
            'userCountsTodayByType' => $this->em->getRepository(User::class)->countUsersRegisteredToday(),
            'userTypesCounts' => $this->em->getRepository(User::class)->countAllUsers(),
            'chart' => $chart,
            'chartToday' => $chartToday,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Olona Talents');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home'),
            MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class),
            MenuItem::subMenu('Roles', 'fa fa-id-card-clip')->setSubItems([
                MenuItem::linkToCrud('Profils', 'fas fa-users', CandidateProfile::class),
                MenuItem::linkToCrud('Entreprises', 'fas fa-building', EntrepriseProfile::class),
                MenuItem::linkToCrud('Coopteurs', 'fas fa-people-robbery', ReferrerProfile::class),
                MenuItem::linkToCrud('Employés', 'fas fa-address-card', Employe::class),
            ]),
            MenuItem::subMenu('Moderation', 'fa fa-wand-magic-sparkles')->setSubItems([
                MenuItem::linkToCrud('Prestations', 'fas fa-hand-holding-hand', Prestation::class),
                MenuItem::linkToCrud('Annonces', 'fas fa-list', JobListing::class),
                MenuItem::linkToCrud('Assignations', 'fas fa-sliders', Assignation::class),
                MenuItem::linkToCrud('Mettings', 'fas fa-handshake', Metting::class),
                MenuItem::linkToCrud('Invitation', 'fas fa-hand-holding-heart', Invitation::class),
            ]),
            MenuItem::subMenu('Modèle économique', 'fa fa-money-bill-transfer')->setSubItems([
                MenuItem::linkToCrud('Packs', 'fas fa-cubes', Package::class),
                MenuItem::linkToCrud('Boosts', 'fas fa-rocket', Boost::class),
                MenuItem::linkToCrud('Boosts Facebook', 'fa-brands fa-facebook', BoostFacebook::class),
                MenuItem::linkToCrud('Type transaction', 'fas fa-money-bill-trend-up', TypeTransaction::class),
            ]),
            MenuItem::linkToCrud('Transaction', 'fas fa-money-check-dollar', Transaction::class),
            MenuItem::subMenu('Configuration', 'fa fa-gears')->setSubItems([
                MenuItem::linkToCrud('Secteurs', 'fas fa-quote-right', Secteur::class),
                MenuItem::linkToCrud('Type de contrat', 'fas fa-layer-group', TypeContrat::class),
                MenuItem::linkToCrud('Type de Service', 'fas fa-layer-group', TypePrestation::class),
                MenuItem::linkToCrud('Devise', 'fas fa-circle-dollar-to-slot', Devise::class),
            ]),
            MenuItem::linkToCrud('Simulations', 'fas fa-vial-virus', Simulateur::class),
            MenuItem::linkToCrud('Modèles Email', 'fas fa-envelope', TemplateEmail::class),
            MenuItem::linkToCrud('Tâches Cron ', 'fas fa-clock-rotate-left', CronLog::class),
            MenuItem::linkToCrud('Errors', 'fas fa-bug', ErrorLog::class),
        ];
    }
}
