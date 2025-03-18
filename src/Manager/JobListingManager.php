<?php

namespace App\Manager;

use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Entity\Vues\AnnonceVues;
use Symfony\Component\Form\Form;
use App\Entity\Entreprise\JobListing;
use App\Entity\Candidate\Applications;
use App\Entity\Entreprise\BudgetAnnonce;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Candidate\ApplicationsRepository;

class JobListingManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationsRepository $applicationsRepository,
    ){}

    public function init(): JobListing
    {
        $jobListing = new JobListing();
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setStatus(JobListing::STATUS_PENDING);
        $jobListing->setIsGenerated(false);
        $jobListing->setJobId(new Uuid(Uuid::v1()));

        return $jobListing;
    }

    public function initBudgetAnnonce(): BudgetAnnonce
    {
        $budget = new BudgetAnnonce();

        return $budget;
    }

    public function save(JobListing $jobListing)
    {
        $this->em->persist($jobListing);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $jobListing = $form->getData();
        $this->save($jobListing);

        return $jobListing;
    }
    
    public function incrementView(JobListing $annonce, string $ipAddress) 
    {        
        $viewRepository = $this->em->getRepository(AnnonceVues::class);
        $existingView = $viewRepository->findOneBy([
            'annonce' => $annonce,
            'idAdress' => $ipAddress,
        ]);

        if (!$existingView) {
            $view = new AnnonceVues();
            $view->setAnnonce($annonce);
            $view->setIdAdress($ipAddress);

            $this->em->persist($view);
            $annonce->addAnnonceVue($view);
            $this->em->flush();
        }
    }
    
    public function isAppliedByCandidate(JobListing $annonce, CandidateProfile $candidat) : array
    {        
        $application = $this->applicationsRepository->findOneBy([
            'candidat' => $candidat,
            'annonce' => $annonce
        ]);
        $applied = false;

        if(!$application instanceof Applications){
            $applied = true;
            $application = new Applications();
            $application->setDateCandidature(new \DateTime());
            $application->setStatus(Applications::STATUS_PENDING);
            $application->setAnnonce($annonce);
            $application->setCvLink($candidat->getCv());
            $application->setCandidat($candidat);
        }

        return [$applied, $application];        
    }
}