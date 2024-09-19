<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Service\FileUploader;
use App\Data\V2\PrestationData;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard/prestation')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private PrestationManager $prestationManager,
        private CreditManager $creditManager,
    ){}
    
    #[Route('/', name: 'app_v2_prestation')]
    public function index(Request $request): Response
    {
        $data = new PrestationData();
        $data->page = $request->get('page', 1);
        $profile = $this->userService->checkProfile();
        if($profile instanceof CandidateProfile){
            $data->candidat = $profile;
        }
        if($profile instanceof EntrepriseProfile){
            $data->entreprise = $profile;
        }

        return $this->render('v2/dashboard/prestation/index.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findSearch($data)
        ]);
    }
    
    #[Route('/create', name: 'app_v2_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setContactEmail($currentUser->getEmail());
        $prestation->setContactTelephone($currentUser->getTelephone());
        $profile = $this->userService->checkProfile();
        if($profile instanceof CandidateProfile){
            $prestation->setCandidateProfile($profile);
            $boostType = 'PRESTATION_CANDIDATE';
        }
        if($profile instanceof EntrepriseProfile){
            $prestation->setEntrepriseProfile($profile);
            $boostType = 'PRESTATION_RECRUITER';
        }
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => $boostType]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $response = $this->handlePrestationSubmission($form->getData(), $currentUser);
            if ($response['success']) {
                $prestation = $this->prestationManager->saveForm($form);
                return $this->redirectToRoute('app_v2_view_prestation', ['prestation' => $prestation->getId()]);
            } else {
                if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                    return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
                }
            }
        }else {

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/prestation/form_errors.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('v2/dashboard/prestation/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    private function handlePrestationSubmission(Prestation $prestation, User $currentUser): array
    {
        $boost = $prestation->getBoost();
        $responseBoost = [];
        
        if ($boost instanceof Boost) {
            if($this->profileManager->canBuy($currentUser, $boost->getCredit())){
                $responseBoost = $this->creditManager->adjustCredits($currentUser, $boost->getCredit());
            }else{
                return ['error' => 'Crédits insuffisants. Veuillez charger votre compte'];
            }
        }
        
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
        $response = $this->creditManager->adjustCredits($currentUser, $creditAmount);
    
        if (!empty($response['error']) || !empty($responseBoost['error'])) {
            $error = $response['error'] ?? $responseBoost['error'];
            return ['success' => false, 'message' => $error, 'status' => '<i class="bi bi-exclamation-octagon me-2"></i> Echec'];
        }
    
        return ['success' => true];
    }
    
    #[Route('/edit/{prestation}', name: 'app_v2_edit_prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => 'PRESTATION_RECRUITER']);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $response = $this->handlePrestationEdit($form->getData(), $currentUser);
            
            if ($response['success']) {
                $this->prestationManager->saveForm($form);
            }
            
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('v2/dashboard/recruiter/live.html.twig', $response);
            }
            
            return $this->render('v2/dashboard/prestation/edit.html.twig', [
                'prestation' => $prestation,
                'form' => $form->createView(),
                'message' => $response['message'],
                'status' => $response['status'],
            ]);
        }

        return $this->render('v2/dashboard/prestation/edit.html.twig', [
            'prestation' => $prestation,
            'form' => $form->createView(),
        ]);
    }

    private function handlePrestationEdit(Prestation $prestation, User $currentUser): array
    {
        $boost = $prestation->getBoost();
        if ($boost instanceof Boost) {
            if($this->profileManager->canBuy($currentUser, $boost->getCredit())){
                $response = $this->creditManager->adjustCredits($currentUser, $boost->getCredit());
            }else{
                return ['success' => false, 'message' => 'Crédits insuffisants. Veuillez charger votre compte', 'status' => '<i class="bi bi-exclamation-octagon me-2"></i> Echec'];
            }
            if (!empty($response['error'])) {
                return ['success' => false, 'message' => $response['error'], 'status' => '<i class="bi bi-exclamation-octagon me-2"></i> Echec'];
            }
        }

        return ['success' => true, 'message' => 'Modification sauvegardée avec succès', 'status' => '<i class="bi bi-check-lg me-2"></i> Succès'];
    }
    
    #[Route('/view/{prestation}', name: 'app_v2_view_prestation')]
    public function viewPrestation(Request $request, Prestation $prestation): Response
    {
        return $this->render('v2/dashboard/prestation/view.html.twig', [
            'prestation' => $prestation,
        ]);
    }
    
    #[Route('/delete', name: 'app_v2_delete_prestation', methods: ['POST'])]
    public function removePrestation(Request $request): Response
    {
        $prestationId = $request->request->get('prestationId');
        $prestation = $this->em->getRepository(Prestation::class)->find($prestationId);
        $message = "La prestation a bien été supprimée";
        $prestation->setStatus(Prestation::STATUS_DELETED);
        $this->em->persist($prestation);
        $this->em->flush();

        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/prestation/delete.html.twig', [
                'prestationId' => $prestationId,
                'message' => $message,
            ]);
        }

        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_prestation');

    }
}
