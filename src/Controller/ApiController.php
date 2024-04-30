<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
#[IsGranted('ROLE_USER')]
class ApiController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,        
    ) {}

    #[Route('/annonces', name: 'app_api_annonces', methods: ['GET'])]
    public function annonces(Request $request): Response
    {
        $page = max($request->query->getInt('page', 1), 1);  
        $perPage = max($request->query->getInt('per_page', 10), 1);  

        $offset = ($page - 1) * $perPage;

        $annonces = $this->jobListingRepository->findBy(
            ['status' => JobListing::STATUS_PUBLISHED],
            ['id' => 'ASC'],  
            $perPage,        
            $offset          
        );

        return $this->json([
            'annonces' => $annonces
        ], 200, [], ['groups' => 'annonce']);
    }

    #[Route('/annonce/{jobId}', name: 'app_api_annonce_view', methods: ['GET'])]
    public function annonce(string $jobId, ValidatorInterface $validator): Response
    {
        $constraints = new Assert\Uuid();
        $errors = $validator->validate($jobId, $constraints);

        if (count($errors) > 0) {
            return $this->json([
                'error' => 'L\'ID fourni n\'est pas un UUID valide'
            ], Response::HTTP_BAD_REQUEST); 
        }

        $annonce = $this->jobListingRepository->findOneBy([
            'jobId' => $jobId
        ]);

        if(!$annonce instanceof JobListing){
            return $this->json([
                'error' => 'Aucune annonce correspondant à cet Id'
            ], Response::HTTP_BAD_REQUEST, [], []);
        }
        
        return $this->json([
            'annonce' => $annonce
        ], Response::HTTP_FOUND, [], ['groups' => 'annonce']);
    }

    #[Route('/candidats', name: 'app_api_candidats', methods: ['GET'])]
    public function candidats(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 50);
        $offset = ($page - 1) * $perPage;
        $candidats = $this->candidateProfileRepository->findTopExperts("", $perPage, $offset);

        return $this->json([
            'candidats' => $candidats
        ], Response::HTTP_FOUND, [], ['groups' => 'identity']);
    }

    #[Route('/candidat/{uuid}', name: 'app_api_candidat_view', methods: ['GET'])]
    public function candidat(string $uuid, ValidatorInterface $validator): Response
    {
        $constraints = new Assert\Uuid();
        $errors = $validator->validate($uuid, $constraints);

        if (count($errors) > 0) {
            return $this->json([
                'error' => 'L\'ID fourni n\'est pas un UUID valide'
            ], Response::HTTP_BAD_REQUEST); 
        }

        $candidat = $this->candidateProfileRepository->findOneBy([
            'uid' => $uuid
        ]);

        if(!$candidat instanceof CandidateProfile){
            return $this->json([
                'error' => 'Aucun candidat correspondant à cet Id'
            ], Response::HTTP_BAD_REQUEST, [], ['groups' => 'identity']);
        }

        return $this->json([
            'candidat' => $candidat
        ], Response::HTTP_FOUND, [], ['groups' => 'identity']);
    }
}
