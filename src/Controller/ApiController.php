<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Entity\Finance\Employe;
use App\Entity\CandidateProfile;
use App\Entity\Finance\Simulateur;
use App\Entity\Entreprise\JobListing;
use App\Entity\Finance\Avantage;
use App\Manager\Finance\EmployeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
#[IsGranted('ROLE_USER')]
class ApiController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $encoder,
        private EmployeManager $employeManager,
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
            ['id' => 'DESC'],  
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

    #[Route('/simulateur', name: 'app_api_simulateur', methods: ['POST'])]
    public function simulateur(Request $request): Response
    {        
        $data = $request->request->all(); 
        try {
            $simulateur = $data["simulateur"];
            $simulation = (new Simulateur())->setCreatedAt(new DateTime());
            $avantage = new Avantage();
            $avantage->setPrimeConnexion((float)$data["simulateur"]["avantage"]["primeConnexion"]);
            $avantage->setPrimeFonction((float)$data["simulateur"]["avantage"]["primeFonction"]);
            $simulation->setAvantage($avantage);
            $email = $data["simulateur"]["user"]["email"];
            $userExist = $this->em->getRepository(User::class)->findOneByEmail($email);
            $devise = $this->em->getRepository(Devise::class)->find($data["simulateur"]["devise"]);
            if($devise instanceof Devise){
                $simulation->setDevise($devise);
            }
            if(!$userExist instanceof User){
                $userExist = new User();
                $userExist->setDateInscription(new DateTime())
                    ->setEmail($email)
                    ->setRoles(['ROLE_EMPLOYE','ROLE_CANDIDAT'])
                    ->setPassword($this->encoder->hashPassword($userExist, $data["simulateur"]["user"]["password"]))
                    ->setNom($data["simulateur"]["user"]["nom"])
                    ->setPrenom($data["simulateur"]["user"]["prenom"])
                    ->setTelephone($data["simulateur"]["user"]["telephone"])
                ;
            }
                $employe = $userExist->getEmploye();
                if(!$employe instanceof Employe){
                    $employe = new Employe();
                    $employe->setUser($userExist);
                    $employe->setSalaireBase(0);
                    $employe->setNombreEnfants((int)$data["simulateur"]["nombreEnfant"]);
                }
            $simulation->setEmploye($employe);
            $simulation->setType($data["simulateur"]["type"]);
            $simulation->setTaux($data["simulateur"]["taux"]);
            $simulation->setStatus($data["simulateur"]["status"]);
            $simulation->setSalaireNet((float)$data["simulateur"]["salaireNet"]);
            $simulation->setNombreEnfant((int)$data["simulateur"]["nombreEnfant"]);
            $simulation->setJourRepas((int)$data["simulateur"]["jourRepas"]);
            $simulation->setJourDeplacement((int)$data["simulateur"]["jourDeplacement"]);
            $simulation->setPrixRepas((int)$data["simulateur"]["prixRepas"]);
            $simulation->setPrixDeplacement((int)$data["simulateur"]["prixDeplacement"]);
            $this->em->persist($avantage);
            $this->em->persist($simulation);
            $this->em->persist($employe);
            $this->em->persist($userExist);
            $this->em->flush();

            return $this->json($simulation, 201, [], ['groups' => 'simulateur']);
        } catch (NotEncodableValueException $e) {
            return $this->json([
                "status" => 400,
                "message" => $e->getMessage()
            ], 400);
        }
    }
}
