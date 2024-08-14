<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Service\FileUploader;
use App\Data\V2\PrestationData;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\PrestationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/prestation')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private PrestationManager $prestationManager,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_prestation')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new PrestationData();
        $data->page = $request->get('page', 1);
        $data->entreprise = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/prestation/index.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findSearch($data)
        ]);
    }
    
    #[Route('/create', name: 'app_v2_recruiter_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setEntrepriseProfile($recruiter);
        $form = $this->createForm(PrestationType::class, $prestation, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->redirectToRoute('app_v2_recruiter_view_prestation', ['prestation' => $prestation->getId()]);
        }

        return $this->render('v2/dashboard/recruiter/prestation/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{prestation}', name: 'app_v2_recruiter_edit_prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $form = $this->createForm(PrestationType::class, $prestation, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->prestationManager->saveForm($form);
            return $this->render('v2/dashboard/recruiter/prestation/edit.html.twig', [
                'prestation' => $prestation,
                'form' => $form->createView(),
                'prestation_description' => $prestation->getDescription()
            ]);
        }

        return $this->render('v2/dashboard/recruiter/prestation/edit.html.twig', [
            'prestation' => $prestation,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/view/{prestation}', name: 'app_v2_recruiter_view_prestation')]
    public function viewPrestation(Request $request, Prestation $prestation): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/prestation/view.html.twig', [
            'prestation' => $prestation,
        ]);
    }
    
    #[Route('/delete/{prestation}', name: 'app_v2_recruiter_delete_prestation')]
    public function removePrestation(Request $request, Prestation $prestation): Response
    {
        $prestationId = $prestation->getId();
        $message = "La prestation a bien été supprimée";
        $this->em->remove($prestation);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/prestation/delete.html.twig', [
                'prestationId' => $prestationId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_prestation');

    }
}
