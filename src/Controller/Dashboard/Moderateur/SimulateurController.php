<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\Finance\Employe;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Repository\Finance\SimulateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

#[Route('/dashboard/moderateur/simulateur')]
class SimulateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ModerateurManager $moderateurManager,
        private SimulateurRepository $simulateurRepository,
    ) {}
    
    #[Route('/', name: 'app_dashboard_moderateur_simulateur')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $simulations = $this->simulateurRepository->findBy([], ['id' => 'DESC']);
        $groupedByEmployee = [];

        foreach ($simulations as $simulation) {
            if($simulation->getEmploye() instanceof Employe){

                $employeId = $simulation->getEmploye()->getId();
    
                if (!array_key_exists($employeId, $groupedByEmployee)) {
                    $groupedByEmployee[$employeId] = [
                        'employe' => $simulation->getEmploye(),
                        'simulations' => [],
                    ];
                }
    
                $groupedByEmployee[$employeId]['simulations'][] = $simulation;
            }
        }

        return $this->render('dashboard/moderateur/simulateur/index.html.twig', [
            'simulations' => $this->simulateurRepository->findBy([], [
                'id' => 'DESC',
            ]),
            'groupes' => $groupedByEmployee
        ]);
    }
}
