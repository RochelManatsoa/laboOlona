<?php

namespace App\Controller\Moderateur;

use App\Entity\Entreprise\JobListing;
use App\Form\Entreprise\JobListing1Type;
use App\Repository\Entreprise\JobListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/moderateur/job/listing')]
class JobListingController extends AbstractController
{
    #[Route('/', name: 'app_moderateur_job_listing_index', methods: ['GET'])]
    public function index(Request $request, JobListingRepository $jobListingRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', null);
        return $this->render('moderateur/job_listing/index.html.twig', [
            'job_listings' => $jobListingRepository->paginateJobListings($status, $page),
            'count' => $jobListingRepository->countAll(),
            // 'countStatus' => $entrepriseProfileRepository->countStatus($status),
            // 'statuses' => array_merge(['Tous' => 'ALL' ],EntrepriseProfile::getStatuses()),
            // 'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_moderateur_job_listing_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jobListing = new JobListing();
        $form = $this->createForm(JobListing1Type::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jobListing);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_job_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/job_listing/new.html.twig', [
            'job_listing' => $jobListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_job_listing_show', methods: ['GET'])]
    public function show(JobListing $jobListing): Response
    {
        return $this->render('moderateur/job_listing/show.html.twig', [
            'job_listing' => $jobListing,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_job_listing_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JobListing $jobListing, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JobListing1Type::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_job_listing_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/job_listing/edit.html.twig', [
            'job_listing' => $jobListing,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_job_listing_delete', methods: ['POST'])]
    public function delete(Request $request, JobListing $jobListing, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jobListing->getId(), $request->request->get('_token'))) {
            $entityManager->remove($jobListing);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_job_listing_index', [], Response::HTTP_SEE_OTHER);
    }
}
