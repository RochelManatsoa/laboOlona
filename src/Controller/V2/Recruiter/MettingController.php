<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Service\User\UserService;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Repository\Moderateur\MettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/metting')]
class MettingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private MettingRepository $mettingRepository,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_metting')]
    public function index(Request $request): Response
    {

        return $this->redirectToRoute('app_tableau_de_bord_entreprise');

        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $role = match ($user->getType()) {
            User::ACCOUNT_CANDIDAT => $user->getCandidateProfile(),
            User::ACCOUNT_ENTREPRISE => $user->getEntrepriseProfile(),
            User::ACCOUNT_MODERATEUR => $user->getModerateurProfile(),
            default => null,
        };

        if ($role instanceof EntrepriseProfile) {
            $mettings = $this->mettingRepository->findBy(['entreprise' => $role], ['id' => 'DESC']);
        } elseif ($role instanceof CandidateProfile) {
            $mettings = $this->mettingRepository->findBy(['candidat' => $role], ['id' => 'DESC']);
        } elseif ($role instanceof ModerateurProfile) {
            $mettings = $this->mettingRepository->findBy(['moderateur' => $role], ['id' => 'DESC']);
        } else {
            $mettings = [];
        }

        return $this->render('v2/dashboard/recruiter/metting/index.html.twig', [
            'mettings' => $this->paginator->paginate(
                $mettings,
                $request->query->getInt('page', 1),
                20
            ),
            'role' => $user->getType(),
        ]);
    }
}
