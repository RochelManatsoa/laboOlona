<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use App\Data\Profile\CandidatSearchData;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\Profile\CandidatSearchFormType;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private PaginatorInterface $paginatorInterface,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
        private UserService $userService,
    ){}

    #[Route('/', name: 'app_dashboard_moderateur_profile_candidat')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new CandidatSearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(CandidatSearchFormType::class, $data);
        $form->handleRequest($request);
        $candidats = $this->candidateProfileRepository->findSearch($data);

        return $this->render('dashboard/moderateur/profile/candidat/index.html.twig', [
            'candidats' => $candidats,
            'form' => $form->createView(),
        ]);
    }
}
