<?php

namespace App\Controller\V2\Recruiter;

use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\EntrepriseManager;
use App\Manager\JobListingManager;
use App\Entity\BusinessModel\Boost;
use App\Form\Entreprise\AnnonceType;
use App\Entity\Entreprise\JobListing;
use App\Manager\BusinessModel\CreditManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/job-application')]
class JobApplicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private JobListingManager $jobListingManager,
        private EntrepriseManager $entrepriseManager,
        private PaginatorInterface $paginator,
        private CreditManager $creditManager,
    ){}

    #[Route('/', name: 'app_v2_recruiter_job_application')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        $jobListings = $this->em->getRepository(JobListing::class)->findJobListingsByEntreprise($recruiter);

        return $this->render('v2/dashboard/recruiter/job_application/index.html.twig', [
            'joblistings' => $this->paginator->paginate(
                $jobListings,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }

    #[Route('/create', name: 'app_v2_recruiter_create_job_application')]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
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
                $this->creditManager->adjustCredits($recruiter->getEntreprise(), $boost->getCredit());
            }
            $this->creditManager->adjustCredits($recruiter->getEntreprise(), 10);
            $this->jobListingManager->saveForm($form);
            return $this->redirectToRoute('app_v2_recruiter_job_lisiting_view', ['id' => $jobListing->getId()]);
        }

        return $this->render('v2/dashboard/recruiter/job_application/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{jobListing}', name: 'app_v2_recruiter_job_application_edit')]
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
            return $this->render('v2/dashboard/recruiter/job_application/edit.html.twig', [
                'jobListing' => $jobListing,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('v2/dashboard/recruiter/job_application/edit.html.twig', [
            'jobListing' => $jobListing,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/view/{jobListing}', name: 'app_v2_recruiter_job_application_view')]
    public function viewJobListing(Request $request, JobListing $jobListing): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/job_application/view.html.twig', [
            'annonce' => $jobListing,
        ]);
    }
    
    #[Route('/delete/{jobListing}', name: 'app_v2_recruiter_delete_job_application')]
    public function removeJobListing(Request $request, JobListing $jobListing): Response
    {
        $jobListingId = $jobListing->getId();
        $message = "L'annonce a bien été supprimée";
        $this->em->remove($jobListing);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recuiter/job_application/delete.html.twig', [
                'jobListingId' => $jobListingId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_job_application');

    }
}
