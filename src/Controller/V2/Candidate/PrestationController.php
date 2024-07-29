<?php

namespace App\Controller\V2\Candidate;

use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Prestation;
use App\Form\Candidate\PrestationType;
use App\Manager\PrestationManager;

#[Route('/v2/candidate/prestation')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private PrestationManager $prestationManager,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_prestation')]
    public function index(Request $request): Response
    {
        // $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();

        return $this->render('v2/dashboard/candidate/prestation/index.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findBy([
                'candidateProfile' => $candidat
            ]),
        ]);
    }
    
    #[Route('/create', name: 'app_v2_candidate_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        // $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setCandidateProfile($candidat);
        $form = $this->createForm(PrestationType::class, $prestation, []);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            dump($form->getData()); // Debugging output
        }
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->redirectToRoute('app_v2_candidate_prestation');
        }

        return $this->render('v2/dashboard/candidate/prestation/create.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findBy([
                'candidateProfile' => $candidat
            ],['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{prestation}', name: 'app_v2_candidate_edit_prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        $candidat = $this->userService->checkProfile();
        $form = $this->createForm(PrestationType::class, $prestation, []);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            dump($form->getData()); // Debugging output
        }
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->redirectToRoute('app_v2_candidate_prestation');
        }

        return $this->render('v2/dashboard/candidate/prestation/create.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findBy([
                'candidateProfile' => $candidat
            ]),
            'form' => $form->createView(),
        ]);
    }
}
