<?php

namespace App\Controller\V2;

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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/v2/dashboard')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
        private OlonaTalentsManager $olonaTalentsManager,
        private CandidatManager $candidatManager,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private ElasticsearchService $elasticsearch,
    ){}
    
    #[Route('/profiles', name: 'app_v2_profiles')]
    public function index(Request $request): Response
    {
        $profile = $this->userService->checkProfile();
        $secteurs = $profile->getSecteurs();
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
        
        return $this->render('v2/dashboard/profile/index.html.twig', [
            'profile' => $profile,
            'candidates' => $candidates,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
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
    
    #[Route('/profile/view/{id}', name: 'app_v2_recruiter_view_profile')]
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
        
        return $this->render('v2/dashboard/profile/view.html.twig', [
            'candidat' => $candidat,
            'type' => $currentUser->getType(),
            'recruiter' => $recruiter,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
            'purchasedContact' => $purchasedContact,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }
}
