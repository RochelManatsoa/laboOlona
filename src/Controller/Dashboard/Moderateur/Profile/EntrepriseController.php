<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use DateTime;
use App\Entity\User;
use App\Twig\AppExtension;
use App\Entity\Notification;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Service\Mailer\MailerService;
use App\Form\Moderateur\EntrepriseType as NewEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use App\Form\Profile\EntrepriseType;
use App\Form\Search\Entreprise\ModerateurEntrepriseSearchType;
use App\Manager\ProfileManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/entreprise')]
class EntrepriseController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private UrlGeneratorInterface $urlGenerator,
        private MailerService $mailerService,
        private PaginatorInterface $paginatorInterface,
        private ModerateurManager $moderateurManager,
        private ProfileManager $profileManager
    ) {}
    
    #[Route('/', name: 'app_dashboard_moderateur_profile_entreprise')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $user = (new User())->setDateInscription(new DateTime())->setType(User::ACCOUNT_ENTREPRISE);
        $entreprise = $this->profileManager->createCompany($user);
        $formEntreprise = $this->createForm(NewEntrepriseType::class, $entreprise);
        $formEntreprise->handleRequest($request);
        if($formEntreprise->isSubmitted() && $formEntreprise->isValid()){
            $this->profileManager->saveCompany($entreprise);
            $this->addFlash('success', 'Entreprise créé avec succès');
        }
        $data = $this->moderateurManager->findAllEntreprise();
        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllEntreprise();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $secteur = $form->get('secteur')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchEntreprise($nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return $this->json([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_entreprises.html.twig', [
                        'entreprises' => $this->paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'form' => $form->createView(),
                        'result' => $data
                    ])
                ]);
            }
        }
        
        return $this->render('dashboard/moderateur/profile/entreprise/index.html.twig', [
            'entreprises' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
            'formEntreprise' => $formEntreprise->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'app_dashboard_moderateur_profile_entreprise_view')]
    public function view(Request $request, EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($entreprise->getEntreprise());
        $notification->setType(Notification::TYPE_PROFIL);
        $notification->setIsRead(false);
        $notification->setTitre("Information sur votre profil Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour  " . $entreprise->getEntreprise()->getFullName() . ",</p><p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p><p>Pour assurer l'efficacité et la qualité de nos services, il est important que chaque profil soit complet et à jour. Voici les informations manquantes :</p><ol><li>[Information manquante 1]</li><li>[Information manquante 2]</li><li>[Autres informations manquantes, si nécessaire]</li></ol><p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p><p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p><p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>
            "
        );

        $form = $this->createForm(NotificationProfileType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'utilisateur */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                $notification->getTitre(),
                "moderateur/notification_profile.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_compte', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé à l\'entreprise');

        }
        return $this->render('dashboard/moderateur/profile/entreprise/view.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form->createView(),
            'assignations' => $this->appExtension->getAssignByEntreprise($entreprise),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_dashboard_moderateur_profile_entreprise_edit')]
    public function edit(Request $request, EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing = $form->getData();
            $this->em->persist($jobListing);
            $this->em->flush();
            $this->addFlash('success', 'Entreprise modifiée avec succès.');

            return $this->redirectToRoute('app_dashboard_moderateur_profile_entreprise');
        }

        return $this->render('dashboard/moderateur/profile/entreprise/edit.html.twig', [
            'form' => $form->createView(),
            'entreprise' => $entreprise,
        ]);
    }
}
