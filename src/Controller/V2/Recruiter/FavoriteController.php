<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Entity\Entreprise\Favoris;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\FavorisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/favorite')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private FavorisRepository $favorisRepository,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_favorite')]
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
            )
        ]);
    }
    
    #[Route('/add/candidate/{id}', name: 'app_v2_recruiter_favorite_add_candidate')]
    public function add(int $id)
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
    
        return $this->json([
            'status' => 'success',
            'message' => 'Candidat ajouté aux favoris avec succès'
        ], Response::HTTP_OK);
    }
    
    #[Route('/delete/candidate/{id}', name: 'app_v2_recruiter_favorite_delete_candidate')]
    public function remove(int $id)
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if($candidat){
            return $this->json(['status' => 'error', 'message' => 'Une erreur est survenue lors de l\'ajout de ce candidat'], Response::HTTP_BAD_REQUEST);
        }

        $favori = $this->favorisRepository->findOneBy([
            'entreprise' => $recruiter,
            'candidat' => $candidat
        ]);

        $this->em->remove($favori);
        $this->em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Candidat retiré des favoris avec succès'
        ], Response::HTTP_OK);
    }
}
