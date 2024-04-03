<?php

namespace App\Controller\Admin;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Errors\ErrorLog;
use App\Entity\Finance\Devise;
use App\Entity\Moderateur\Assignation;
use App\Entity\Moderateur\Invitation;
use App\Entity\Moderateur\Metting;
use App\Entity\Moderateur\TypeContrat;
use App\Entity\ReferrerProfile;
use App\Entity\Secteur;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        return $this->render('admin/index.html.twig');
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
                MenuItem::linkToCrud('Coopteurs', 'fas fa-address-card', ReferrerProfile::class),
            ]),
            MenuItem::subMenu('Moderation', 'fa fa-wand-magic-sparkles')->setSubItems([
                MenuItem::linkToCrud('Assignations', 'fas fa-sliders', Assignation::class),
                MenuItem::linkToCrud('Mettings', 'fas fa-handshake', Metting::class),
                MenuItem::linkToCrud('Invitation', 'fas fa-hand-holding-heart', Invitation::class),
            ]),
            MenuItem::subMenu('Configuration', 'fa fa-gears')->setSubItems([
                MenuItem::linkToCrud('Secteurs', 'fas fa-quote-right', Secteur::class),
                MenuItem::linkToCrud('Type de contrat', 'fas fa-layer-group', TypeContrat::class),
                MenuItem::linkToCrud('Devise', 'fas fa-circle-dollar-to-slot', Devise::class),
            ]),
            MenuItem::linkToCrud('Errors', 'fas fa-bug', ErrorLog::class),
        ];
    }
}
