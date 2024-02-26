<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use Twig\Environment;
use App\Form\JobListingType;
use App\Entity\AffiliateTool;
use App\Service\User\UserService;
use App\Entity\Finance\Simulateur;
use App\Form\RegisterFormType;
use App\Security\AppAuthenticator;
use App\Form\Finance\SimulateurType;
use App\Entity\Entreprise\JobListing;
use App\Entity\Finance\Employe;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\ContactForm;
use App\Form\Finance\SimuType;
use App\Manager\Finance\EmployeManager;
use App\Service\Annonce\AnnonceService;
use App\Form\Moderateur\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private SecteurRepository $secteurRepository,
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private AnnonceService $annonceService,
        private EmployeManager $employeManager,
        private RequestStack $requestStack,
        private Environment $twig
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'sectors' => $this->secteurRepository->findAll(),
            'candidats' => $this->candidateProfileRepository->findTopExperts(),
            'topRanked' => $this->candidateProfileRepository->findTopRanked(),
            'annonces' => $this->jobListingRepository->findBy([
                'status' => JobListing::STATUS_PUBLISHED,
            ]),
        ]);
    }

    #[Route('/contact', name: 'app_home_contact')]
    public function contact(Request $request): Response
    {
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $this->em->persist($contactForm);
            $this->em->flush();
            $this->mailerService->send(
                "contact@olona-talents.com",
                "Nouvelle entrée sur le formulaire de contact",
                "contact.html.twig",
                [
                    'user' => $contactForm,
                ]
            );
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');
        }

        return $this->render('home/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/service', name: 'app_home_service')]
    public function service(): Response
    {
        return $this->render('home/service.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/legal-mentions', name: 'app_home_legal')]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/privacy-policy', name: 'app_home_privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/terms-and-conditions', name: 'app_home_terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/formulaire', name: 'app_home_form')]
    public function form(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $annonceId = $request->request->get('annonce_id');
            $jobId = $request->request->get('job_id');
            $this->annonceService->add($annonceId);
            if (!$this->getUser()) {
                return $this->redirectToRoute('app_login');
            }
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $jobId]);
        }
    }

    #[Route('/simulateur-portage-salarial', name: 'app_home_simulateur_portage')]
    public function simulateur(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
    ): Response {
        $session = $this->requestStack->getSession();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $simulateur = (new Simulateur())->setCreatedAt(new DateTime());
        $connected = false;
        if ($user) {
            $connected = true;
            $employe = $user->getEmploye();
            if(!$employe instanceof Employe){
                $employe = new Employe();
                $employe->setUser($user);
            }
            $simulateur->setEmploye($employe);
        }
        $session->set('utilisateurEstConnecte', $connected);
        $form = $this->createForm(SimulateurType::class, $simulateur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->employeManager->simulate($simulateur);
            $simulateur = $form->getData();
            $employe = $simulateur->getEmploye();
            $user = $simulateur->getEmploye()->getUser();
            $employe->setNombreEnfants($form->get('nombreEnfant')->getData());
            $employe->setSalaireBase($result['salaire_de_base_ariary']);

            if (!$connected) {
                $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if($existingUser instanceof User){
                    $currentRoles = $existingUser->getRoles();
                    if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                        $currentRoles[] = 'ROLE_EMPLOYE'; 
                    }
                    $existingEmploye = $user->getEmploye();
                    if(!$existingEmploye instanceof Employe){
                        $existingEmploye = new Employe();
                        $existingEmploye->setUser($existingUser);
                    }
                    $existingUser->setRoles($currentRoles);
                    $existingEmploye->setUser($existingUser);
                    $existingEmploye->setNombreEnfants($form->get('nombreEnfant')->getData());
                    $existingEmploye->setSalaireBase($result['salaire_de_base_ariary']);
                    $simulateur->setEmploye($existingEmploye);
                    $this->em->persist($existingEmploye);
                    $this->em->persist($simulateur);
                    $this->em->flush();
                    $this->addFlash('success', 'Vous avez déjà un compte Olona Talents, veuillez vous connecté pour pour voir le resultats des simulations');

                    return $this->redirectToRoute('app_home_simulateur_portage');
                }else{
                    $currentRoles = $user->getRoles();
                    if (!in_array('ROLE_EMPLOYE', $currentRoles)) {
                        $currentRoles[] = 'ROLE_EMPLOYE'; 
                    }
                    $user->setRoles($currentRoles);
                    $user->setDateInscription(new DateTime());
                    $user->setType(User::ACCOUNT_EMPLOYE);
                    $user->setRoles(['ROLE_EMPLOYE']);
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $form->get('employe')->get('user')->get('plainPassword')->getData()
                        )
                    );
                    $this->em->persist($user);
                }
            }
            $this->em->persist($employe);
            $this->em->persist($simulateur);
            $this->em->flush();
            $session->set('simulation', [$simulateur->getId() => $result]);

            if (!$connected) {
                return $userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );
            }

            return $this->redirectToRoute('app_dashboard_employes_simulation_view', ['id' => $simulateur->getId()]);
        }

        return $this->render('home/simulateur-portage-salarial.html.twig', [
            'form' => $form->createView(),
            'connected' => $connected,
        ]);
    }
}
