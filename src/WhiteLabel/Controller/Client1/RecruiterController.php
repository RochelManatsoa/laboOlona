<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Manager\Client1\CVThequeManager;
use App\WhiteLabel\Manager\Client1\JobListingManager;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Repository\Client1\Entreprise\JobListingRepository;
use App\WhiteLabel\Repository\Client1\Candidate\ApplicationsRepository;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/recruiter')]
class RecruiterController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_client1_recruiter')]
    public function index(): Response
    {
        return $this->render('white_label/client1/recruiter/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/candidatures', name: 'app_white_label_client1_recruiter_candidatures')]
    public function candidatures(Request $request, ApplicationsRepository $applicationsRepository): Response
    {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();
        $entreprise = $user?->getEntrepriseProfile();

        if (!$entreprise) {
            return $this->redirectToRoute('app_white_label_client1_user_profile');
        }

        $page = $request->query->getInt('page', 1);
        $applications = $applicationsRepository->findByEntrepriseProfile($page, $entreprise->getId());

        return $this->render('white_label/client1/recruiter/candidatures.html.twig', [
            'applications' => $applications,
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/cvtheque', name: 'app_white_label_client1_recruiter_cvtheque')]
    public function cvtheque(Request $request, CVThequeManager $cVThequeManager): Response
    {
        $params = [];
        $size = $request->query->get('size', 10);
        $page = $request->query->get('page', 1);
        $from = ($page - 1) * $size;
        $title = $request->query->get('filter-title', null);
        $gender = $request->query->get('filter-gender', null);
        $province = $request->query->get('filter-province', null);
        $experienceYears = $request->query->get('filter-experience-years', null);

        $filters = [];
        if ($gender) $filters['gender'] = $gender;
        if ($province) $filters['province'] = $province;
        if ($experienceYears) $filters['experience_years'] = $experienceYears;
        
        $searchResults = $cVThequeManager->searchEntities('candidates', $from, $size, $title, $filters);
        $totalPages = ceil($searchResults['totalResults'] / $size);
        $params['searchResults'] = $searchResults['entities'];
        $params['totalResults'] = $searchResults['totalResults'];
        $params['totalPages'] = $totalPages;
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['filterTitle'] = $title;

        return $this->render('white_label/client1/recruiter/cvtheque.html.twig', $params);
    }

    #[Route('/creer-une-annonce', name: 'app_white_label_client1_recruiter_creer_une_annonce')]
    public function newJobOffer(Request $request, \App\WhiteLabel\Manager\Client1\JobListingManager $jobListingManager): Response
    {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();
        $entreprise = $user?->getEntrepriseProfile();

        if (!$entreprise) {
            return $this->redirectToRoute('app_white_label_client1_user_profile');
        }

        $jobListing = $jobListingManager->init($entreprise);
        $form = $this->createForm(\App\WhiteLabel\Form\Client1\JobListing1Type::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobListingManager->save($jobListing);
            $this->addFlash('success', 'Annonce créée avec succès');

            return $this->redirectToRoute('app_white_label_client1_recruiter_toutes_les_annonces');
        }

        return $this->render('white_label/client1/recruiter/creer_une_annonce.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonces', name: 'app_white_label_client1_recruiter_toutes_les_annonces')]
    public function jobOffers(
        Request $request,
        JobListingRepository $jobListingRepository,
        JobListingManager $jobListingManager,
        PaginatorInterface $paginatorInterface
    ): Response {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();
        $entreprise = $user?->getEntrepriseProfile();

        if (!$entreprise) {
            return $this->redirectToRoute('app_white_label_client1_user_profile');
        }

        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', JobListing::STATUS_PUBLISHED);

        $offres = $jobListingRepository->paginateJobListingsEntrepriseProfiles(
            $entreprise,
            $page,
            $status,
            $paginatorInterface
        );

        return $this->render('white_label/client1/recruiter/annonces.html.twig', [
            'entreprise' => $entreprise,
            'offres' => $offres,
            'count' => $jobListingRepository->countAllByEntreprise($entreprise),
            'countStatus' => $jobListingRepository->countStatusByEntreprise($entreprise, $status),
            'statuses' => $jobListingManager->getStatuses(),
            'labels' => JobListing::getLabels(),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/profil-candidat/{id}', name: 'app_white_label_client1_recruiter_view_profile')]
    public function viewProfile(int $id): Response
    {
        $candidate = $this->entityManager->getRepository(CandidateProfile::class)->find($id);

        if (!$candidate || $candidate->getStatus() === CandidateProfile::STATUS_BANNISHED || $candidate->getStatus() === CandidateProfile::STATUS_PENDING) {
            throw $this->createNotFoundException("Nous sommes désolés, mais le candidat demandé n'existe pas.");
        }

        $experiences = $candidate->getExperiences()->toArray();
        usort($experiences, fn($a, $b) => ($b->getDateDebut() <=> $a->getDateDebut()));

        $competences = $candidate->getCompetences()->toArray();
        usort($competences, fn($a, $b) => ($b->getNote() <=> $a->getNote()));

        $langages = $candidate->getLangages()->toArray();
        usort($langages, fn($a, $b) => ($b->getNiveau() <=> $a->getNiveau()));

        return $this->render('white_label/client1/recruiter/view_profile.html.twig', [
            'candidat' => $candidate,
            'experiences' => $experiences,
            'competences' => $competences,
            'langages' => $langages,
        ]);
    }
}
