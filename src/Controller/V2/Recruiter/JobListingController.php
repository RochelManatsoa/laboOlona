<?php

namespace App\Controller\V2\Recruiter;

use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\EntrepriseManager;
use App\Manager\JobListingManager;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use App\Form\Entreprise\AnnonceType;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/job-listing')]
class JobListingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private JobListingManager $jobListingManager,
        private EntrepriseManager $entrepriseManager,
        private PaginatorInterface $paginator,
        private CreditManager $creditManager,
        private ProfileManager $profileManager,
    ){}

    #[Route('/', name: 'app_v2_recruiter_job_listing')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $jobListings = $this->em->getRepository(JobListing::class)->findJobListingsByEntreprise($recruiter);

        return $this->render('v2/dashboard/recruiter/job_listing/index.html.twig', [
            'joblistings' => $this->paginator->paginate(
                $jobListings,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }

    #[Route('/create', name: 'app_v2_recruiter_create_job_listing')]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var JobListing $jobListing */
        $jobListing = $this->jobListingManager->init();
        $devise = $this->entrepriseManager->getEntrepriseDevise($recruiter);
        $budget = $this->jobListingManager->initBudgetAnnonce();
        $budget->setCurrency($devise);
        $jobListing->setEntreprise($recruiter);
        $jobListing->setBudgetAnnonce($budget); 

        $form = $this->createForm(AnnonceType::class, $jobListing, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $jobListing = $form->getData();
            $boost = $jobListing->getBoost();
            if($boost instanceof Boost){
                $responseBoost = $this->creditManager->adjustCredits($recruiter->getEntreprise(), $boost->getCredit());
            }

            $message = 'Annonce sauvegardée avec succès';
            $success = true;
            $status = 'Succès';
        
            $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_OFFER);
            $response = $this->creditManager->adjustCredits($this->userService->getCurrentUser(), $creditAmount);
            
            if (isset($response['error']) || isset($responseBoost['error'])) {
                $message = $response['error'];
                $success = false;
                $status = 'Echec';
            }

            if (isset($response['success']) && isset($responseBoost['success'])) {
                $this->jobListingManager->saveForm($form);
            }

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/recruiter/live.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'credit' => $currentUser->getCredit()->getTotal(),
                ]);
            }

            return $this->redirectToRoute('app_v2_recruiter_job_lisiting_view', ['id' => $jobListing->getId()]);
        }

        return $this->render('v2/dashboard/recruiter/job_listing/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{jobListing}', name: 'app_v2_recruiter_job_listing_edit')]
    public function editJobListing(Request $request, JobListing $jobListing): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $form = $this->createForm(AnnonceType::class, $jobListing, []);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $boost = $jobListing->getBoost();
            if($boost instanceof Boost){
                $this->creditManager->adjustCredits($recruiter->getEntreprise(), $boost->getCredit());
            }
            $this->jobListingManager->saveForm($form);
            return $this->render('v2/dashboard/recruiter/job_listing/edit.html.twig', [
                'jobListing' => $jobListing,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('v2/dashboard/recruiter/job_listing/edit.html.twig', [
            'jobListing' => $jobListing,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/view/{id}', name: 'app_v2_recruiter_job_lisiting_view')]
    public function viewJobListing(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $jobListing = $this->em->getRepository(JobListing::class)->find($id);

        return $this->render('v2/dashboard/recruiter/job_listing/view.html.twig', [
            'annonce' => $jobListing,
        ]);
    }
    
    #[Route('/delete/{jobListing}', name: 'app_v2_recruiter_delete_job_listing')]
    public function removeJobListing(Request $request, JobListing $jobListing): Response
    {
        $jobListingId = $jobListing->getId();
        $message = "L'annonce a bien été supprimée";
        $this->em->remove($jobListing);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recuiter/job_listing/delete.html.twig', [
                'jobListingId' => $jobListingId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_job_listing');

    }
}
