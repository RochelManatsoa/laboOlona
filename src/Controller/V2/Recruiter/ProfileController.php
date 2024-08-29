<?php

namespace App\Controller\V2\Recruiter;

use App\Data\V2\ProfileData;
use App\Entity\BusinessModel\PurchasedContact;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Vues\CandidatVues;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Manager\OlonaTalentsManager;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
        private OlonaTalentsManager $olonaTalentsManager,
        private CandidatManager $candidatManager,
        private ElasticsearchService $elasticsearch,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_profile')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $this->userService->checkProfile();
        $data = new ProfileData();
        $data->page = $request->get('page', 1);
        $data->entreprise = $entreprise;
        $params = [];

        $query = $request->query->get('q', $entreprise->getSecteurs()[0]->getNom());
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['searchQuery'] = $query;
        $paramsCandidates = $this->olonaTalentsManager->getParamsCandidates($from, $size, $query);
        
        $candidates = $this->elasticsearch->search($paramsCandidates);
        $totalCandidatesResults = $candidates['hits']['total']['value'];
        $totalProfilePages = ceil($totalCandidatesResults / $size);
        $params['totalPages'] = $totalProfilePages;
        $params['candidats'] = $candidates['hits']['hits'];
        $params['totalCandidatesResults'] = $totalCandidatesResults;
        
        return $this->render('v2/dashboard/recruiter/profile/index.html.twig', $params);
    }
    
    #[Route('/view/{id}', name: 'app_v2_recruiter_view_profile')]
    public function viewProfile(Request $request, int $id): Response
    {
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $recruiter = $this->userService->checkProfile();
        if(!$recruiter instanceof EntrepriseProfile){
            $recruiter = null;
        }

        $ipAddress = $request->getClientIp();
        $viewRepository = $this->em->getRepository(CandidatVues::class);
        $existingView = $viewRepository->findOneBy([
            'candidat' => $candidat,
            'ipAddress' => $ipAddress,
        ]);

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $candidat->getCandidat(),
        ]);

        if (!$existingView) {
            $view = new CandidatVues();
            $view->setCandidat($candidat);
            $view->setIpAddress($ipAddress);
            $view->setCreatedAt(new \DateTime());

            $this->em->persist($view);
            $candidat->addVue($view);
            $this->em->flush();
        }
        
        return $this->render('v2/dashboard/recruiter/profile/view.html.twig', [
            'candidat' => $candidat,
            'type' => $currentUser->getType(),
            'recruiter' => $recruiter,
            'purchasedContact' => $purchasedContact,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }
}
