<?php

namespace App\WhiteLabel\Controller\Client1\Crud;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Form\Client1\JobListing1Type;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/offre-emploi')]
class JobListingController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_job_listing_index', methods: ['GET'])]
    public function index(
        Request $request, 
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = true;
        $job_listings = $this->entityManager->getRepository(JobListing::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId);

        $status = $request->query->get('status', null);
        return $this->render('white_label/client1/admin/job_listing/index.html.twig', [
            'job_listings' => $job_listings
        ]);
    }

    #[Route('/new', name: 'app_white_label_job_listing_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $jobListing = new JobListing();
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setJobId(new Uuid(Uuid::v1()));
        $jobListing->setStatus(JobListing::STATUS_PENDING);
        $form = $this->createForm(JobListing1Type::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($jobListing);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_job_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/job_listing/new.html.twig', [
            'job_listing' => $jobListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_job_listing_show', methods: ['GET'])]
    public function show(JobListing $jobListing): Response
    {
        return $this->render('white_label/client1/admin/job_listing/show.html.twig', [
            'job_listing' => $jobListing,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_job_listing_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JobListing $jobListing): Response
    {
        $form = $this->createForm(JobListing1Type::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($jobListing);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_job_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/job_listing/edit.html.twig', [
            'job_listing' => $jobListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_job_listing_delete', methods: ['POST'])]
    public function delete(Request $request, JobListing $jobListing): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jobListing->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($jobListing);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_job_listing_index', [], Response::HTTP_SEE_OTHER);
    }
}
