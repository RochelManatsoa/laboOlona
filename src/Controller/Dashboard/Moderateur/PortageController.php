<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Data\Finance\SearchData;
use App\Data\Profile\PortageSearchData;
use App\Entity\Finance\Contrat;
use App\Entity\Finance\Simulateur;
use App\Entity\Secteur;
use App\Form\Moderateur\Finance\PortageSearchFormType;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Search\Secteur\SecteurSearchType;
use App\Form\Simulateur\SearchForm;
use App\Manager\Finance\EmployeManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class PortageController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private EmployeManager $employeManager,
    ) {}

    #[Route('/portages', name: 'app_dashboard_moderateur_portage')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new SearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchForm::class, $data);
        $form->handleRequest($request);
        $contrats = $this->em->getRepository(Contrat::class)->findSearch($data);

        return $this->render('dashboard/moderateur/portage/index.html.twig', [
            'contrats' => $contrats,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/portage/{id}/view', name: 'app_dashboard_moderateur_view_portage')]
    public function viewPortage(Request $request, Contrat $contrat): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $simulateur = $contrat->getSimulateur();

        return $this->render('dashboard/moderateur/portage/view.html.twig', [
            'portage' => $contrat,
            'simulateur' => $simulateur,
            'results' => $this->employeManager->simulate($simulateur),
            'button_label' => 'Mettre à jour',
        ]);
    }
}
