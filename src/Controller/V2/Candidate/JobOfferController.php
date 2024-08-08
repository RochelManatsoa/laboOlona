<?php

namespace App\Controller\V2\Candidate;

use App\Data\V2\JobOfferData;
use App\Service\User\UserService;
use App\Manager\OlonaTalentsManager;
use App\Entity\Entreprise\JobListing;
use App\Service\ElasticsearchService;
use App\Entity\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/job-offer')]
class JobOfferController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ElasticsearchService $elasticsearch,
        private OlonaTalentsManager $olonaTalentsManager,
        private ApplicationsRepository $applicationsRepository,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_job_offer')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        $data = new JobOfferData();
        $data->page = $request->get('page', 1);
        $data->candidat = $candidat;
        $params = [];

        $query = $request->query->get('q', $candidat->getSecteurs()[0]->getNom());
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['searchQuery'] = $query;
        $paramsJoblisting = $this->olonaTalentsManager->getParamsJoblisting($from, $size, $query);
        
        $joblistings = $this->elasticsearch->search($paramsJoblisting);
        $totalJobListingsResults = $joblistings['hits']['total']['value'];
        $totalAnnoncesPages = ceil($totalJobListingsResults / $size);
        $params['totalAnnoncesPages'] = $totalAnnoncesPages;
        $params['annonces'] = $joblistings['hits']['hits'];
        $params['totalJobListingsResults'] = $totalJobListingsResults;

        return $this->render('v2/dashboard/candidate/job_offer/index.html.twig', $params);
    }
    
    #[Route('/view/{id}', name: 'app_v2_candidate_view_job_offer')]
    public function viewJobOffer(int $id): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $candidat = $this->userService->checkProfile();
        if(!$annonce instanceof JobListing){
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_v2_candidate_job_offer');
        }
        $application = $this->applicationsRepository->findOneBy([
            'candidat' => $candidat,
            'annonce' => $annonce
        ]);

        $applied = false;

        if(!$application instanceof Applications){
            $applied = true;
            $application = new Applications();
            $application->setDateCandidature(new \DateTime());
            $application->setAnnonce($annonce);
            $application->setCvLink($candidat->getCv());
            $application->setCandidat($candidat);
            $application->setStatus(Applications::STATUS_PENDING);
        }
        return $this->render('v2/dashboard/candidate/job_offer/view.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
            'applied' => $applied,
        ]);
    }
}
