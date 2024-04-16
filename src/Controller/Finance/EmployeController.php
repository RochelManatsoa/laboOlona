<?php

namespace App\Controller\Finance;

use DateTime;
use App\Entity\User;
use App\Entity\Notification;
use App\Entity\Finance\Employe;
use App\Entity\Finance\Avantage;
use App\Form\Finance\EmployeType;
use App\Service\User\UserService;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerService;
use App\Form\Search\EmployesSearchType;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Finance\EmployeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/finance/employe')]
class EmployeController extends AbstractController
{
    public function __construct(
        private EmployeManager $employeManager,
        private EntityManagerInterface $em,
        private EmployeRepository $employeRepository,
        private UserRepository $userRepository,
        private PaginatorInterface $paginatorInterface,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private UserService $userService,
    ){        
    }

    #[Route('/', name: 'app_finance_employe')]
    public function index(Request $request): Response
    {
        $data = $this->employeRepository->findBy([], ['id' => 'DESC']);
        /** Formulaire de recherche annonces */
        $form = $this->createForm(EmployesSearchType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $data = $this->employeManager->searchEmployes($nom);
        }

        return $this->render('finance/employe/index.html.twig', [
            'employes' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'form' => $form->createView()
        ]);
    }

    #[Route('/new', name: 'app_finance_employe_new')]
    public function new(Request $request): Response
    {
        $employe = $this->employeManager->init();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $employe = $form->getData();
            $user = $employe->getUser();
            $checkUser = $this->userRepository->findOneBy([
                'email' => $user->getEmail()
            ]);
            if($checkUser instanceof User){
                $currentRoles = $checkUser->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $employe->setUser($checkUser);
            }else{
                $currentRoles = $user->getRoles();
                if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                    $currentRoles[] = 'ROLE_EMPLOYE'; 
                }
                $user->setDateInscription(new DateTime())->setRoles(['ROLE_EMPLOYE']);
            }
            $this->em->persist($employe);
            $this->em->flush();
            $this->addFlash('success', 'Employé ajouté');

            return $this->redirectToRoute('app_finance_employe_view', ['id' => $employe->getId()]);
        }

        return $this->render('finance/employe/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_finance_employe_edit')]
    public function edit(Request $request, Employe $employe): Response
    {
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $user = $form->getData()->getUser();
            $currentRoles = $user->getRoles();
            if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                $currentRoles[] = 'ROLE_EMPLOYE'; 
            }
            $user->setDateInscription(new DateTime())->setRoles($currentRoles);
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Modification effectuée');

            return $this->redirectToRoute('app_finance_employe_view', ['id' => $employe->getId()]);
        }

        return $this->render('finance/employe/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_finance_employe_view')]
    public function view(Request $request, Employe $employe): Response
    {
        if(!$employe->getAvantage() instanceof Avantage){
            $employe->setAvantage(new Avantage());
            $this->em->persist($employe);
            $this->em->flush();
        }
        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($employe->getUser());
        $notification->setType(Notification::TYPE_RELANCE);
        $notification->setIsRead(false);
        $notification->setTitre('Simulateur de salaire sur Olona Talents');
        $notification->setContenu(
            '
            <p>Bonjour '.$employe->getUser()->getFullName().',</p>
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
                $employe->getUser()->getEmail(),
                $notification->getTitre(),
                "finance/relance_simulation.html.twig",
                [
                    'user' => $employe->getUser(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_employes_simulations', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'finance@olona-talents.com'
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }
        
        return $this->render('finance/employe/view.html.twig', [
            'employe' => $employe,
            'form' => $form->createView(),
        ]);
    }
}
