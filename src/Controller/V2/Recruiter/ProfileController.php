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
        /** @var EntrepriseProfile $recruiter */
        $recruiter = $this->userService->checkProfile();
        $secteurs = $recruiter->getSecteurs();
        $page = $request->query->get('page', 1);
        $limit = 10;
        $qb = $this->em->getRepository(CandidateProfile::class)->createQueryBuilder('c');

        $qb->join('c.secteurs', 's') 
        ->where('c.status = :status')
        ->setParameter('status', CandidateProfile::STATUS_VALID)
        ->andWhere('s IN (:secteurs)') 
        ->setParameter('secteurs', $secteurs)
        ->orderBy('c.id', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult(($page - 1) * $limit);

        $candidates = $qb->getQuery()->getResult();
        
        return $this->render('v2/dashboard/recruiter/profile/index.html.twig', [
            'recruiter' => $recruiter,
            'candidates' => $candidates,
            'nextPage' => $page + 1,
            'hasMore' => count($candidates) == $limit
        ]);
    }

    #[Route('/api/candidate-secteurs', name: 'api_candidate_secteurs')]
    public function apiCandidates(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $recruiter = $this->userService->checkProfile(); 
        $secteurs = $recruiter->getSecteurs();
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $qb = $this->em->getRepository(CandidateProfile::class)->createQueryBuilder('c');
        $qb->join('c.secteurs', 's') 
        ->where('c.status = :status')
        ->setParameter('status', CandidateProfile::STATUS_VALID)
        ->andWhere('s IN (:secteurs)') 
        ->setParameter('secteurs', $secteurs)
        ->orderBy('c.id', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult(($page - 1) * $limit);

        $candidates = $qb->getQuery()->getResult();

        return $this->render('v2/dashboard/result/parts/_part_candidates_list.html.twig', [
            'candidates' => $candidates,
            'recruiter' => $recruiter,
        ]);
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
