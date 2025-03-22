<?php

namespace App\Controller\Moderateur;

use App\Entity\EntrepriseProfile;
use App\Form\EntrepriseProfileType;
use App\Entity\Entreprise\JobListing;
use App\Manager\Marketing\LeadManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Marketing\SourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/moderateur/recruiter/profile')]
class EntrepriseProfileController extends AbstractController
{
    #[Route('/', name: 'app_moderateur_entreprise_profile_index', methods: ['GET'])]
    public function index(
        Request $request, 
        EntrepriseProfileRepository $entrepriseProfileRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', null);

        return $this->render('moderateur/entreprise_profile/index.html.twig', [
            'entreprise_profiles' => $entrepriseProfileRepository->paginateEntrepriseProfiles($page, $status),
            'count' => $entrepriseProfileRepository->countAll(),
            'countStatus' => $entrepriseProfileRepository->countStatus($status),
            'statuses' => array_merge(['Tous' => 'ALL' ],EntrepriseProfile::getStatuses()),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_moderateur_entreprise_profile_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        LeadManager $leadManager,
        SourceRepository $sourceRepository,
    ): Response
    {
        $sourceEntreprise = $sourceRepository->findOneBy(['slug' => 'entreprise']);
        $entrepriseProfile = new EntrepriseProfile();
        $form = $this->createForm(EntrepriseProfileType::class, $entrepriseProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($entrepriseProfile->getStatus() !== EntrepriseProfile::STATUS_BANNED && $entrepriseProfile->getStatus() !== EntrepriseProfile::STATUS_PENDING ){
                $lead = $leadManager->init();
                $lead->setSource($sourceEntreprise);
                $lead->setComment('Création entreprise - '.$entrepriseProfile->getDescription());
                $lead->setFullName($entrepriseProfile->getEntreprise());
                $lead->setEmail($entrepriseProfile->getEntreprise()->getEmail());
                $lead->setPhone($entrepriseProfile->getEntreprise()->getTelephone());
                $lead->setUser($entrepriseProfile->getEntreprise());
                $leadManager->save($lead);
            }
            $entityManager->persist($entrepriseProfile);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_entreprise_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/entreprise_profile/new.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_entreprise_profile_show', methods: ['GET'])]
    public function show(EntrepriseProfile $entrepriseProfile): Response
    {
        return $this->render('moderateur/entreprise_profile/show.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_entreprise_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        EntrepriseProfile $entrepriseProfile, 
        EntityManagerInterface $entityManager,
        LeadManager $leadManager,
        SourceRepository $sourceRepository,
    ): Response
    {
        $sourceEntreprise = $sourceRepository->findOneBy(['slug' => 'entreprise']);
        $form = $this->createForm(EntrepriseProfileType::class, $entrepriseProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($entrepriseProfile->getStatus() !== EntrepriseProfile::STATUS_BANNED && $entrepriseProfile->getStatus() !== EntrepriseProfile::STATUS_PENDING ){
                $lead = $leadManager->init();
                $lead->setSource($sourceEntreprise);
                $lead->setComment('Validation entreprise - '.$entrepriseProfile->getDescription());
                $lead->setFullName($entrepriseProfile->getEntreprise());
                $lead->setEmail($entrepriseProfile->getEntreprise()->getEmail());
                $lead->setPhone($entrepriseProfile->getEntreprise()->getTelephone());
                $lead->setUser($entrepriseProfile->getEntreprise());
                $leadManager->save($lead);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_entreprise_profile_show', ['id' => $entrepriseProfile->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/entreprise_profile/edit.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_entreprise_profile_delete', methods: ['POST'])]
    public function delete(Request $request, EntrepriseProfile $entrepriseProfile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entrepriseProfile->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entrepriseProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_entreprise_profile_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/job/offers', name: 'app_moderateur_entreprise_profile_job_offers', methods: ['GET', 'POST'])]
    public function jobOffers(
        Request $request, 
        EntrepriseProfile $entrepriseProfile, 
        JobListingRepository $jobListingRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', null);

        return $this->render('moderateur/entreprise_profile/job_offers.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
            'job_listings' => $jobListingRepository->paginateJobListingsEntrepriseProfiles($entrepriseProfile, $page, $status),
            'count' => $jobListingRepository->countAllByEntreprise($entrepriseProfile),
            'countStatus' => $jobListingRepository->countStatusByEntreprise($entrepriseProfile,$status),
            'statuses' => array_merge(['Tous' => 'ALL' ],JobListing::getStatuses()),
            'selectedStatus' => $status,
        ]);
    }
}
