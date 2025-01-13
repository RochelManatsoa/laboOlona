<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Prestation;
use App\Security\EmailVerifier;
use App\Service\ActivityLogger;
use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Form\RegistrationFormType;
use App\Security\AppAuthenticator;
use Symfony\Component\Mime\Address;
use App\Manager\OlonaTalentsManager;
use App\Entity\Entreprise\JobListing;
use App\Service\ElasticsearchService;
use App\Entity\Moderateur\ContactForm;
use App\Form\Moderateur\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Service\Mailer\MailerService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class OlonaTalentsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidatRepository,
        private JobListingRepository $annonceRepository,
        private ElasticsearchService $elasticsearch,
        private OlonaTalentsManager $olonaTalentsManager,
        private CreditManager $creditManager,
        private PaginatorInterface $paginatorInterface,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private Security $security,
        private UserService $userService,
        private ActivityLogger $activityLogger,
        private RequestStack $requestStack,
        private MailerService $mailerService,
    ) {}

    #[Route('/', name: 'app_home', options: ['sitemap' => true])]
    public function index(): Response
    {
        if($this->security->getUser()){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        return $this->render('v2/home.html.twig', [
            'candidats' => $this->candidatRepository->findBy(
                ['status' => CandidateProfile::STATUS_VALID],
                ['id' => 'DESC'],
                18
            ),
        ]);
    }

    #[Route('/upgrade', name: 'app_olona_talents_upgrade')]
    public function upgrade(): Response
    {
        return $this->render('v2/upgrade.html.twig', []);
    }

    #[Route('/premium', name: 'app_olona_talents_premium')]
    public function premium(): Response
    {
        return $this->render('v2/premium.html.twig', []);
    }

    #[Route('/register', name: 'app_olona_talents_register', options: ['sitemap' => true])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EmailVerifier $emailVerifier,
    ): Response {
        $typology = $request->query->get('typology', null);
        $this->requestStack->getSession()->set('typology', ucfirst($typology));
        $user = new User();
        $user->setDateInscription(new DateTime());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $this->em->persist($user);
            $this->em->flush();
            $this->creditManager->ajouterCreditsBienvenue($user, 200);

            $emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('support@olona-talents.com', 'Olona Talents'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre inscription sur olona-talents.com')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['user' => $user])
            );

            return $this->redirectToRoute('app_email_sending');

        }

        return $this->render('v2/olona_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/result/candidates', name: 'app_olona_talents_candidates')]
    public function candidates(Request $request): Response
    {
        return $this->fetchAndRender($request, 'candidates');
    }

    #[Route('/result/joblistings', name: 'app_olona_talents_joblistings')]
    public function joblistings(Request $request): Response
    {
        return $this->fetchAndRender($request, 'joblistings');
    }

    #[Route('/result/prestations', name: 'app_olona_talents_prestations')]
    public function prestations(Request $request): Response
    {
        return $this->fetchAndRender($request, 'prestations');
    }

    private function fetchAndRender(Request $request, string $type): Response
    {
        $query = $request->query->get('q');
        $size = $request->query->getInt('size', 6);
        $from = $request->query->getInt('from', 0);
        $params = [];
        $currentUser = $this->userService->getCurrentUser();
        if($currentUser){
            $profile = $this->userService->checkUserProfile($currentUser);
        }

        if ($currentUser) {
            $this->activityLogger->logSearchActivity($currentUser, $query, $type);
        }

        $searchResults = $this->olonaTalentsManager->searchEntities($type, $from, $size, $query);
        $params[$type] = $searchResults['entities'];
        $params['totalResults'] = $searchResults['totalResults'];
        $params['action'] = $this->urlGeneratorInterface->generate('app_olona_talents_' . $type);
        $params['hasMore'] = $searchResults['hasMore'];
        $params['from'] = $from;

        $boostEntities = $this->olonaTalentsManager->getBoostedEntities($type, $from, $size, $query);
        $params[$type . '_boost'] = $boostEntities;

        if ($request->isXmlHttpRequest()) {
            $htmlContent = $this->renderView("v2/dashboard/result/parts/_part_{$type}_list.html.twig", $params);
            return $this->json([
                'content' => $htmlContent,
                'hasMore' => $params['hasMore'],
            ]);
        }
        if ($currentUser && $profile) {
            return $this->render("v2/dashboard/result/{$type}_result.html.twig", $params);
        }

        return $this->render("v2/dashboard/result/default_{$type}_result.html.twig", $params);
    }

    #[Route('/view/prestation/{id}', name: 'app_olona_talents_view_prestation')]
    public function viewprestation(int $id): Response
    {
        $currentUser = $this->security->getUser();
        $prestation = $this->em->getRepository(Prestation::class)->find($id);
        if ($currentUser instanceof User) {
            return $this->redirectToRoute('app_v2_view_prestation', ['prestation' => $prestation->getId()]);
        }

        return $this->redirectToRoute('app_connect', []);
    }

    #[Route('/view/recruiter/{id}', name: 'app_olona_talents_view_recruiter')]
    public function viewRecruiter(int $id): Response
    {
        return $this->render('v2/upgrade.html.twig', []);
    }

    #[Route('/v2/contact', name: 'app_contact', options: ['sitemap' => true])]
    public function support(Request $request): Response
    {
        $contactForm = new ContactForm;
        $contactForm->setCreatedAt(new \DateTime());
        $form = $this->createForm(ContactFormType::class, $contactForm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactForm = $form->getData();
            $this->em->persist($contactForm);
            $this->em->flush();
            $this->mailerService->sendMultiple(
                ["contact@olona-talents.com", "support@olonatalents.com", "support@olonatalents.com"],
                "Nouvelle entrée sur le formulaire de contact",
                "contact.html.twig",
                [
                    'user' => $contactForm,
                ]
            );
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');
        }

        return $this->render('v2/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
