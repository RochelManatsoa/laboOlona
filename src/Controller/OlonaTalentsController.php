<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Prestation;
use App\Security\EmailVerifier;
use App\Entity\CandidateProfile;
use App\Form\RegistrationFormType;
use App\Security\AppAuthenticator;
use Symfony\Component\Mime\Address;
use App\Manager\OlonaTalentsManager;
use App\Entity\Entreprise\JobListing;
use App\Service\ElasticsearchService;
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
use App\Service\ActivityLogger;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    ) {}

    #[Route('/', name: 'app_home')]
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

    #[Route('/v2/olona-register', name: 'app_olona_talents_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EmailVerifier $emailVerifier,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
    ): Response {
        $typology = $request->query->get('typology', null);
        $this->requestStack->getSession()->set('typology', $typology);
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

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('v2/olona_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/result', name: 'app_olona_talents_result')]
    public function result(Request $request): Response
    {
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params = [];
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $params['type'] = $currentUser->getType();
        }
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['searchQuery'] = $query;

        $paramsCandidate = $this->olonaTalentsManager->getParamsCandidates($from, $size, $query);
        $paramsCandidatePremium = $this->olonaTalentsManager->getParamsPremiumCandidates($from, $size, $query);

        $paramsJoblisting = $this->olonaTalentsManager->getParamsJoblisting($from, $size, $query);
        $paramsJoblistingPremium = $this->olonaTalentsManager->getParamsPremiumJoblisting($from, $size, $query);

        $paramsPrestation = $this->olonaTalentsManager->getParamsPrestations($from, $size, $query);
        $paramsPrestationPremium = $this->olonaTalentsManager->getParamsPremiumPrestations($from, $size, $query);

        $candidates = $this->elasticsearch->search($paramsCandidate);
        $totalCandidatesResults = $candidates['hits']['total']['value'];
        $totalPages = ceil($totalCandidatesResults / $size);
        $params['totalPages'] = $totalPages;
        $params['candidats'] = $candidates['hits']['hits'];
        $params['totalCandidatesResults'] = $totalCandidatesResults;

        $premiums = $this->elasticsearch->search($paramsCandidatePremium);
        $params['top_candidats'] = $this->paginatorInterface->paginate(
            $premiums['hits']['hits'],
            $page,
            8
        );

        $joblistings = $this->elasticsearch->search($paramsJoblisting);
        $totalJobListingsResults = $joblistings['hits']['total']['value'];
        $totalAnnoncesPages = ceil($totalJobListingsResults / $size);
        $params['totalAnnoncesPages'] = $totalAnnoncesPages;
        $params['annonces'] = $joblistings['hits']['hits'];
        $params['totalJobListingsResults'] = $totalJobListingsResults;

        $premiumJoblistings = $this->elasticsearch->search($paramsJoblistingPremium);
        $params['top_annonces'] = $this->paginatorInterface->paginate(
            $premiumJoblistings['hits']['hits'],
            $page,
            8
        );

        $prestations = $this->elasticsearch->search($paramsPrestation);
        $params['prestations'] = $prestations['hits']['hits'];
        $totalPrestationsResults = $prestations['hits']['total']['value'];
        $totalPrestationsPages = ceil($totalPrestationsResults / $size);
        $params['totalPrestationsPages'] = $totalPrestationsPages;
        $params['totalPrestationsResults'] = $totalPrestationsResults;

        $premiumPrestations = $this->elasticsearch->search($paramsPrestationPremium);
        $params['top_prestations'] = $this->paginatorInterface->paginate(
            $premiumPrestations['hits']['hits'],
            $page,
            8
        );
        $params['action'] = $this->urlGeneratorInterface->generate('app_olona_talents_result');

        if ($currentUser) {
            return $this->render('v2/dashboard/result.html.twig', $params);
        }

        return $this->render('v2/result.html.twig', $params);
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
        $size = $request->query->getInt('size', 10);
        $from = $request->query->getInt('from', 0);
        $params = [];
        
        if($this->userService->getCurrentUser()){
            $this->activityLogger->logSearchActivity($this->userService->getCurrentUser(), $query, $type);
        }

        if ($type === 'candidates') {
            $paramsSearch = $this->olonaTalentsManager->getParamsCandidates($from, $size, $query);
        } elseif ($type === 'joblistings') {
            $paramsSearch = $this->olonaTalentsManager->getParamsJoblisting($from, $size, $query);
        } else {
            $paramsSearch = $this->olonaTalentsManager->getParamsPrestations($from, $size, $query);
        }

        $searchResults = $this->elasticsearch->search($paramsSearch);

        $ids = array_map(fn($hit) => $hit['_id'], $searchResults['hits']['hits']);

        $repository = $this->getRepositoryForType($type);
        $entities = $repository->findBy(['id' => $ids]);

        usort($entities, function ($a, $b) use ($ids) {
            return array_search($a->getId(), $ids) <=> array_search($b->getId(), $ids);
        });

        $params[$type] = $entities;
        $params['action'] = $this->urlGeneratorInterface->generate('app_olona_talents_'. $type);
        $params['totalResults'] = $searchResults['hits']['total']['value'];

        if ($request->isXmlHttpRequest()) {
            return $this->render("v2/dashboard/result/parts/_part_{$type}_list.html.twig", $params);
        }

        return $this->render("v2/dashboard/result/{$type}_result.html.twig", $params);
    }

    private function getRepositoryForType(string $type)
    {
        switch ($type) {
            case 'candidates':
                return $this->em->getRepository(CandidateProfile::class);
            case 'joblistings':
                return $this->em->getRepository(JobListing::class);
            case 'prestations':
                return $this->em->getRepository(Prestation::class);
            default:
                throw new \InvalidArgumentException("Invalid type: $type");
        }
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
}
