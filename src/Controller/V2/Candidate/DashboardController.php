<?php

namespace App\Controller\V2\Candidate;

use App\Service\FileUploader;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Profile\Candidat\CandidateUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Controller\Dashboard\Moderateur\OpenAi\CandidatController;
use App\Entity\Prestation;

#[Route('/v2/candidate/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private CandidatController $candidatController,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_dashboard')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        $form = $this->createForm(CandidateUploadType::class, $candidat);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $cvFile = $form->get('cv')->getData();
            $this->em->persist($candidat);
            $this->em->flush();
            if ($cvFile) {
                $fileName = $this->fileUploader->upload($cvFile, $candidat);
                $candidat->setCv($fileName[0]);
                $this->profileManager->saveCV($fileName, $candidat);
            }

            $response = $this->candidatController->analyse(new \Symfony\Component\HttpFoundation\Request(), $candidat);
            $content = $response->getContent(); 
            $data = json_decode($content, true);
            if ($data['status'] == 'success') {
                $this->addFlash('success', 'Analyse effectué avec succès');
            } else {
                $this->addFlash('danger', 'Une erreur s\'est produite lors de l\'analyse de votre cv');
            }
            
            return $this->redirectToRoute('app_v2_dashboard');
        }

        return $this->render('v2/dashboard/candidate/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
