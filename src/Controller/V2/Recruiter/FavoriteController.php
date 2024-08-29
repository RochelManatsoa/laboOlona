<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Entreprise\Favoris;
use App\Entity\BusinessModel\Credit;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
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
        private CandidatManager $candidatManager,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
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
    
    #[Route('/view/{uid}', name: 'app_v2_recruiter_favorite_view')]
    public function view(Request $request, CandidateProfile $candidat): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/favorite/view.html.twig', [
            'candidat' => $candidat,
            'recruiter' => $recruiter,
            'experiences' => $this->candidatManager->getExperiencesSortedByDate($candidat),
            'competences' => $this->candidatManager->getCompetencesSortedByNote($candidat),
            'langages' => $this->candidatManager->getLangagesSortedByNiveau($candidat),
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
            'status' => 'succes',
            'message' => 'Candidat retiré des favoris avec succès'
        ], Response::HTTP_OK);
    }

    #[Route('/show-contact', name: 'app_v2_recruiter_favorite_show_contact_candidate', methods: ['POST', 'GET'])]
    public function showContact(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $candidatId = $request->request->get('candidatId');
        $message = 'Contact du candidat affiché';
        $success = true;
        $status = 'Succès';
    
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);
        $response = $this->creditManager->adjustCredits($currentUser, $creditAmount);
    
        if (isset($response['error'])) {
            $message = $response['error'];
            $success = false;
            $status = 'Echec';
        }
    
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($candidatId);
        if (!$candidat) {
            $message = 'Candidat non trouvé.';
            $success = false;
            $status = 'Echec';
        }

        $purchasedContact = new PurchasedContact();
        $purchasedContact->setBuyer($currentUser);
        $purchasedContact->setPurchaseDate(new \DateTime());
        $purchasedContact->setContact($candidat->getCandidat());
        $purchasedContact->setPrice($creditAmount);
        $this->em->persist($purchasedContact);
        $this->em->flush();
        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/turbo/live.html.twig', [
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'user' => $candidat->getCandidat(),
                'credit' => $currentUser->getCredit()->getTotal(),
            ]);
        }
        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_connect');
    }
}
