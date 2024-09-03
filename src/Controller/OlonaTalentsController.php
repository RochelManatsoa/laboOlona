<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Entity\CandidateProfile;
use App\Entity\Prestation;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use App\Manager\OlonaTalentsManager;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OlonaTalentsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidatRepository,
        private JobListingRepository $annonceRepository,
        private ElasticsearchService $elasticsearch,
        private OlonaTalentsManager $olonaTalentsManager,
        private PaginatorInterface $paginatorInterface,
        private Security $security,
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EmailVerifier $emailVerifier): Response
    {
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

            $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
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

    #[Route('/result', name: 'app_olona_talents_result')]
    public function result(Request $request): Response
    {
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params = [];
        $currentUser = $this->security->getUser();
        if($currentUser instanceof User){
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

        // dd($params);
        // dd($candidates, $joblistings, $entreprises);
        if($currentUser){
            return $this->render('v2/dashboard/result.html.twig', $params);
        }

        return $this->render('v2/result.html.twig', $params);
    }

    #[Route('/view/prestation/{id}', name: 'app_olona_talents_view_prestation')]
    public function viewprestation(int $id): Response
    {
        $currentUser = $this->security->getUser();
        $prestation = $this->em->getRepository(Prestation::class)->find($id);
        if($currentUser instanceof User){
            if($currentUser->getType() === User::ACCOUNT_CANDIDAT){
                return $this->redirectToRoute('app_v2_candidate_view_prestation', ['prestation' => $prestation->getId()]);
            }
            if($currentUser->getType() === User::ACCOUNT_ENTREPRISE){
                return $this->redirectToRoute('app_v2_recruiter_view_prestation', ['prestation' => $prestation->getId()]);
            }
            return $this->redirectToRoute('app_connect', []);
        }
        
        return $this->redirectToRoute('app_connect', []);
    }

    #[Route('/view/recruiter/{id}', name: 'app_olona_talents_view_recruiter')]
    public function viewRecruiter(int $id): Response
    {
        return $this->render('v2/upgrade.html.twig', []);
    }
}