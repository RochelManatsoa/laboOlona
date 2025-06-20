<?php

namespace App\WhiteLabel\Controller\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Manager\Client1\CVThequeManager;
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
    public function candidatures(): Response
    {
        return $this->render('white_label/client1/recruiter/candidatures.html.twig', [
            'controller_name' => 'UserController',
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
    public function newJobOffer(): Response
    {
        return $this->render('white_label/client1/recruiter/creer_une_annonce.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/annonces', name: 'app_white_label_client1_recruiter_toutes_les_annonces')]
    public function jobOffers(): Response
    {
        return $this->render('white_label/client1/recruiter/annonces.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/assistance', name: 'app_white_label_client1_recruiter_assistance')]
    public function assistance(): Response
    {
        return $this->render('white_label/client1/recruiter/assistance.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
