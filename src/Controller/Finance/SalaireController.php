<?php

namespace App\Controller\Finance;

use App\Entity\Finance\Employe;
use DateTime;
use App\Entity\Notification;
use App\Entity\Finance\Simulateur;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Finance\EmployeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use App\Repository\Finance\SimulateurRepository;
use App\Service\Mailer\MailerService;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/finance/salaire')]
class SalaireController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmployeManager $employeManager,
        private EmployeRepository $employeRepository,
        private UserService $userService,
        private SimulateurRepository $simulateurRepository,
        private PaginatorInterface $paginatorInterface,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
    ){        
    }
    
    #[Route('/', name: 'app_finance_salaire')]
    public function index(Request $request): Response
    {
        $simulations = $this->simulateurRepository->findSimulationsWithEmploye();
        foreach ($simulations as $simulation) {
            $salaireNetAriary = $this->employeManager->convertEuroToAriary($simulation->getSalaireNet(), $simulation->getDevise()->getTaux());
            $simulation->setForfait($salaireNetAriary); // Si vous pouvez ou souhaitez stocker temporairement la valeur convertie
        }
        
        // Tri des simulations en fonction du salaireNet maintenant converti
        usort($simulations, function($a, $b) {
            return $b->getForfait() <=> $a->getForfait(); // Tri descendant
        });// Initialisation des tableaux pour les différentes plages de forfait
        $array1 = []; // Plus de 4M ariary
        $array2 = []; // Entre 4M et 3M ariary
        $array3 = []; // Entre 3M et 2M ariary
        $array4 = []; // Entre 2M et 1M ariary
        $array5 = []; // Moins de 1M ariary
        
        foreach ($simulations as $simulation) {
            $forfait = $simulation->getForfait();
            
            if ($forfait > 4000001) {
                $array1[] = $simulation;
            } elseif ($forfait > 3000001 && $forfait <= 4000000) {
                $array2[] = $simulation;
            } elseif ($forfait > 2000001 && $forfait <= 3000000) {
                $array3[] = $simulation;
            } elseif ($forfait > 1000001 && $forfait <= 2000000) {
                $array4[] = $simulation;
            } elseif ($forfait <= 1000000) {
                $array5[] = $simulation;
            }
        }
        
        return $this->render('finance/salaire/index.html.twig', [
            'simulations1' => $this->paginatorInterface->paginate(
                $array1,
                $request->query->getInt('page', 1),
                10
            ),
            'simulations2' => $this->paginatorInterface->paginate(
                $array2,
                $request->query->getInt('page', 1),
                10
            ),
            'simulations3' => $this->paginatorInterface->paginate(
                $array3,
                $request->query->getInt('page', 1),
                10
            ),
            'simulations4' => $this->paginatorInterface->paginate(
                $array4,
                $request->query->getInt('page', 1),
                10
            ),
            'simulations5' => $this->paginatorInterface->paginate(
                $array5,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/view/{id}', name: 'app_finance_salaire_view')]
    public function view(Request $request, Simulateur $simulateur): Response
    {   
        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($simulateur->getEmploye()->getUser());
        $notification->setType(Notification::TYPE_RELANCE);
        $notification->setIsRead(false);
        $notification->setTitre('Simulateur de salaire sur Olona Talents');
        $notification->setContenu(
            '
            <p>Bonjour '.$simulateur->getEmploye()->getUser()->getFullName().',</p>
            <p>Nous espérons que vous avez trouvé notre outil de simulation de salaire utile pour mieux comprendre vos perspectives salariales !</p>

            <p>Nous avons remarqué que vous aviez récemment utilisé notre simulateur de salaire le [date de la simulation], et nous souhaitions nous assurer que vous avez reçu toutes les informations dont vous avez besoin pour avancer dans votre carrière.</p>

            <p>Si vous avez des questions concernant vos résultats ou si vous souhaitez discuter des opportunités qui pourraient correspondre à vos attentes salariales, n\'hésitez pas à prendre contact avec nous. Nous serions ravis de vous aider à évaluer vos options et à planifier vos prochains pas.</p>

            <p>Pour plus de détails ou pour revoir vos résultats de simulation, vous pouvez accéder à votre compte sur [lien vers la plateforme]. Si vous souhaitez discuter directement avec un de nos conseillers, vous pouvez prendre rendez-vous ici [lien pour prendre rendez-vous].</p>

            <p>Nous vous remercions de votre confiance en Olona-Talents et nous réjouissons de vous accompagner dans votre parcours professionnel.</p>

            <p>Cordialement,</p>

            [Votre Nom]<br>
            Équipe Olona-Talents
            '
        );
        $form = $this->createForm(NotificationProfileType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'utilisateur */
            $this->mailerService->send(
                $simulateur->getEmploye()->getUser()->getEmail(),
                $notification->getTitre(),
                "finance/relance_simulation.html.twig",
                [
                    'user' => $simulateur->getEmploye()->getUser(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_employes_simulations', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'finance@olona-talents.com'
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }

        return $this->render('finance/salaire/view.html.twig', [
            'simulateur' => $simulateur,
            'form' => $form->createView(),
            'results' => $this->employeManager->simulate($simulateur),
        ]);
    }

    #[Route('/employe/{id}', name: 'app_finance_salaire_employe')]
    public function employeSimulations(Request $request, Employe $employe): Response
    {   
        return $this->render('finance/salaire/employe.html.twig', [
            'simulations' => $this->paginatorInterface->paginate(
                $employe->getSimulateurs(),
                $request->query->getInt('page', 1),
                10
            ),
            'employe' => $employe,
        ]);
    }
}
