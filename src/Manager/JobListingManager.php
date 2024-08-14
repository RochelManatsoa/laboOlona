<?php

namespace App\Manager;

use App\Entity\Entreprise\BudgetAnnonce;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Form\Form;

class JobListingManager
{
    public function __construct(
        private EntityManagerInterface $em,
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
}