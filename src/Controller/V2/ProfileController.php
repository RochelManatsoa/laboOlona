<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Twig\AppExtension;
use App\Service\ActivityLogger;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\Vues\CandidatVues;
use App\Service\User\UserService;
use App\Entity\BusinessModel\Credit;
use App\Manager\OlonaTalentsManager;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use App\Manager\ProfileManager;
use Google\Service\CivicInfo\Candidate;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        private ActivityLogger $activityLogger,
        private AppExtension $appExtension,
        private ElasticsearchService $elasticsearch,
        private ProfileManager $profileManager,
    ){}
    
    #[Route('/profiles', name: 'app_v2_profiles')]
    public function index(Request $request): Response
    {
        return $this->redirectToRoute('app_connect');

        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
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
        $html = "";
        if(count($candidates) > 0){
            $html = $this->renderView('v2/dashboard/result/parts/_part_candidates_list.html.twig', [
                'candidates' => $candidates,
                'recruiter' => $recruiter
            ]);
        }
    
        return $this->json([
            'html' => $html,
            'hasMore' => count($candidates) == $limit,
            'count' => count($candidates) ,
        ]);
    }
    
    #[Route('/profile/view/{id}', name: 'app_v2_recruiter_view_profile')]
    public function viewProfile(Request $request, int $id): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = ['id' => $id];
        
        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if ($candidat === null || $candidat->getStatus() === CandidateProfile::STATUS_BANNISHED || $candidat->getStatus() === CandidateProfile::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais le candidat demandé n\'existe pas.');
        }
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $recruiter = $this->userService->checkProfile();
        if($recruiter == $candidat){
            return $this->redirectToRoute('app_v2_candidate_dashboard');
        }
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
        $this->activityLogger->logProfileViewActivity($currentUser, $this->appExtension->generatePseudo($candidat));
        
        return $this->render('v2/dashboard/profile/view_candidate.html.twig', [
            'candidat' => $candidat,
            'type' => $currentUser->getType(),
            'recruiter' => $recruiter,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
            'purchasedContact' => $purchasedContact,
            'show_candidate_price' => $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE),
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }
    
    #[Route('/recruiter/view/{id}', name: 'app_v2_view_recruiter_profile')]
    public function viewRecruiterProfile(Request $request, int $id): Response   
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = ['id' => $id];
        
        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);

        $recruiter = $this->em->getRepository(EntrepriseProfile::class)->find($id);
        if ($recruiter === null || $recruiter->getStatus() === EntrepriseProfile::STATUS_BANNED || $recruiter->getStatus() === EntrepriseProfile::STATUS_PENDING) {
            throw $this->createNotFoundException('Nous sommes désolés, mais l\'entreprise demandée n\'existe pas.');
        }
        
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $candidat = $this->userService->checkProfile();
        if($recruiter == $candidat){
            return $this->redirectToRoute('app_v2_recruiter_dashboard');
        }
        if(!$candidat instanceof CandidateProfile){
            $candidat = null;
        }

        $contactRepository = $this->em->getRepository(PurchasedContact::class);
        $purchasedContact = $contactRepository->findOneBy([
            'buyer' => $currentUser,
            'contact' => $recruiter->getEntreprise(),
        ]);

        $this->activityLogger->logProfileViewActivity($currentUser, $this->appExtension->generateReference($recruiter));
        
        return $this->render('v2/dashboard/profile/view_recruiter.html.twig', [
            'candidat' => $candidat,
            'type' => $currentUser->getType(),
            'recruiter' => $recruiter,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
            'purchasedContact' => $purchasedContact,
            'show_recruiter_price' => $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_RECRUITER),
        ]);
    }
}
