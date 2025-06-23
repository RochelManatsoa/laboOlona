<?php

namespace App\WhiteLabel\Manager\Client1;

use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;

class ApplicationManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    public function init(): Applications
    {
        $application = new Applications();
        $application->setDateCandidature(new \DateTime());
        $application->setStatus(Applications::STATUS_PENDING);

        return $application;
    }

    public function save(Applications $application): void
    {
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    public function saveForm(Form $form): Applications
    {
        $application = $form->getData();
        $this->save($application);

        return $application;
    }
}
