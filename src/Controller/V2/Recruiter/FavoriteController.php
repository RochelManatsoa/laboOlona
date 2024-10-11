<?php

namespace App\Controller\V2\Recruiter;

use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Entreprise\Favoris;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\FavorisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/v2/dashboard')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private FavorisRepository $favorisRepository,
        private PaginatorInterface $paginator,
        private CandidatManager $candidatManager,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}
    
    #[Route('/favorites', name: 'app_v2_recruiter_favorite')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $favorites = $this->favorisRepository->findBy([
            'entreprise' => $recruiter,
        ]);

        return $this->render('v2/dashboard/recruiter/favorite/index.html.twig', [
            'favorites' => $this->paginator->paginate(
                $favorites,
                $request->query->getInt('page', 1),
                20
            ),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
        ]);
    }
    
    #[Route('/favorite/view/{uid}', name: 'app_v2_recruiter_favorite_view')]
    public function view(Request $request, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/favorite/view.html.twig', [
            'candidat' => $candidat,
            'recruiter' => $recruiter,
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
        ]);
    }
    
    #[Route('/favorite/add/candidate/{id}', name: 'app_v2_recruiter_favorite_add_candidate')]
    public function add(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if(!$candidat){
            return $this->json(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'ajout de ce candidat'], Response::HTTP_BAD_REQUEST);
        }

        $favoris = $this->favorisRepository->findOneBy([
            'entreprise' => $recruiter,
            'candidat' => $candidat
        ]);

        if($favoris){
            return $this->json(['status' => 'error', 'message' => 'Ce candidat est déjà dans vos favoris'], Response::HTTP_OK);
        }

        $favori = new Favoris();
        $favori->setEntreprise($recruiter);
        $favori->setCandidat($candidat);
        $favori->setCreatedAt(new \DateTime());
    
        $this->em->persist($favori);
        $this->em->flush();
        $favoriteId = $candidat->getId();
        $message = 'Candidat ajouté aux favoris avec succès';
        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/dashboard/recruiter/favorite/update.html.twig', [
                'message' => $message,
                'add' => true,
                'favoriteId' => $favoriteId,
            ]);
        }
    
        return $this->json([
            'status' => 'success',
            'message' => $message,
        ], Response::HTTP_OK);
    }
    
    #[Route('/favorite/delete/candidate/{id}', name: 'app_v2_recruiter_favorite_delete_candidate')]
    public function remove(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if(!$candidat){
            return $this->json(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'ajout de ce candidat'], Response::HTTP_BAD_REQUEST);
        }

        $favori = $this->favorisRepository->findOneBy([
            'entreprise' => $recruiter,
            'candidat' => $candidat
        ]);
        $favoriteId = $favori->getId();
        $candidateId = $candidat->getId();

        $this->em->remove($favori);
        $this->em->flush();

        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/favorite/delete.html.twig', [
                'favoriteId' => $favoriteId,
                'candidateId' => $candidateId,
                'message' => "Candidat effacé de vos favoris",
            ]);
        }

        return $this->json([
            'status' => 'succes',
            'message' => 'Candidat retiré des favoris avec succès'
        ], Response::HTTP_OK);
    }
}
