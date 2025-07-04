<?php

namespace App\WhiteLabel\Manager\Client1;

use App\WhiteLabel\Entity\Client1\User;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\Logs\ActivityLog;
use App\WhiteLabel\Entity\Client1\Vues\AnnonceVues;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Entity\Client1\Entreprise\BudgetAnnonce;
use App\WhiteLabel\Entity\Client1\Entreprise\PrimeAnnonce;
use App\WhiteLabel\Entity\Client1\Finance\Devise;
use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class JobListingManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    public function init(EntrepriseProfile $recruiter): JobListing
    {
        $defaultDevise = $this->entityManager->getRepository(Devise::class)->findOneBy(['slug' => 'ariary']);
        $devise = $recruiter->getDevise() instanceof Devise ? $recruiter->getDevise() : $defaultDevise;
        $budget = $this->initBudgetAnnonce($devise);
        $primeAnnonce = $this->initPrimeAnnonce($devise);

        $jobListing = new JobListing();
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setStatus(JobListing::STATUS_PENDING);
        $jobListing->setIsGenerated(false);
        $jobListing->setJobId(new Uuid(Uuid::v1()));
        $jobListing->setEntreprise($recruiter);
        $jobListing->setBudgetAnnonce($budget);
        $jobListing->setPrimeAnnonce($primeAnnonce);

        return $jobListing;
    }

    public function initPrimeAnnonce(Devise $devise): PrimeAnnonce
    {
        $primeAnnonce = new PrimeAnnonce();
        $primeAnnonce->setCreatedAt(new \DateTime());
        $primeAnnonce->setDevise($devise);
        $primeAnnonce->setSymbole($devise->getSymbole());
        $primeAnnonce->setTaux($devise->getTaux());

        return $primeAnnonce;
    }

    public function initBudgetAnnonce(Devise $devise): BudgetAnnonce
    {
        $budget = new BudgetAnnonce();
        $budget->setCurrency($devise);

        return $budget;
    }

    public function save(JobListing $jobListing)
    {
        $this->entityManager->persist($jobListing);
        $this->entityManager->flush();
    }

    public function saveForm(Form $form)
    {
        $jobListing = $form->getData();
        $this->save($jobListing);

        return $jobListing;
    }
    
    public function incrementView(JobListing $annonce, string $ipAddress) 
    {        
        $viewRepository = $this->entityManager->getRepository(AnnonceVues::class);
        $existingView = $viewRepository->findOneBy([
            'annonce' => $annonce,
            'idAdress' => $ipAddress,
        ]);

        if (!$existingView) {
            $view = new AnnonceVues();
            $view->setAnnonce($annonce);
            $view->setIdAdress($ipAddress);

            $this->entityManager->persist($view);
            $annonce->addAnnonceVue($view);
            $this->entityManager->flush();
        }
    }
    
    public function isAppliedByCandidate(JobListing $annonce, CandidateProfile $candidat) : array
    {        
        $application = $this->entityManager->getRepository(Applications::class)->findOneBy([
            'candidat' => $candidat,
            'annonce' => $annonce
        ]);
        $applied = true;

        if(!$application instanceof Applications){
            $applied = false;
            $application = new Applications();
            $application->setDateCandidature(new \DateTime());
            $application->setStatus(Applications::STATUS_PENDING);
            $application->setAnnonce($annonce);
            $application->setCvLink($candidat->getCv());
            $application->setCandidat($candidat);
        }

        return [$applied, $application];        
    }

    public function getStatuses(): array
    {
        return [
            'Toutes' => 'ALL',
            'Publiée' => JobListing::STATUS_PUBLISHED ,
            'En attente' => JobListing::STATUS_PENDING ,
            'Expirée' => JobListing::STATUS_EXPIRED ,
            'Effacée' => JobListing::STATUS_DELETED ,
            'Boostée' => JobListing::STATUS_FEATURED ,
        ];
    }
}