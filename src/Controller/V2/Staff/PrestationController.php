<?php

namespace App\Controller\V2\Staff;

use App\Entity\Prestation;
use App\Service\FileUploader;
use App\Data\V2\PrestationData;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Manager\PrestationManager;
use App\Form\PrestationStaffType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/staff/prestation')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private PrestationManager $prestationManager,
    ){}
    
    #[Route('/', name: 'app_v2_staff_prestation')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new PrestationData();
        $data->page = $request->get('page', 1);

        return $this->render('v2/dashboard/staff/prestation/index.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findSearch($data)
        ]);
    }
    
    #[Route('/create', name: 'app_v2_staff_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $candidat = $this->userService->checkProfile();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setCandidateProfile($candidat);
        $form = $this->createForm(PrestationStaffType::class, $prestation, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->redirectToRoute('app_v2_staff_prestation');
        }

        return $this->render('v2/dashboard/staff/prestation/create.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findBy([
                'candidateProfile' => $candidat
            ],['id' => 'DESC']),
            'form' => $form->createView(),
            'new' => true,
        ]);
    }
    
    #[Route('/edit/{prestation}', name: 'app_v2_staff_edit_prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(PrestationStaffType::class, $prestation, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->redirectToRoute('app_v2_staff_prestation');
        }

        return $this->render('v2/dashboard/staff/prestation/create.html.twig', [
            'form' => $form->createView(),
            'prestation' => $prestation,
            'new' => false,
        ]);
    }
    
    #[Route('/view/{prestation}', name: 'app_v2_staff_view_prestation')]
    public function viewPrestation(Prestation $prestation): Response
    {
        return $this->render('v2/dashboard/staff/prestation/view.html.twig', [
            'prestation' => $prestation,
        ]);
    }
}
