<?php

namespace App\Manager;

use DateTime;
use Symfony\Component\Form\Form;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use App\Entity\Moderateur\Assignation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssignationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private JobListingRepository $jobListingRepository,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private UserService $userService
    ){}

    public function init(): Assignation
    {
        $assignation = new Assignation();
        $assignation
            ->setStatus(Assignation::STATUS_PENDING)
            ->setDateAssignation(new DateTime())
            ;
        
        return $assignation;
    }

    public function save(Assignation $assignation)
    {
        $this->em->persist($assignation);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $assignation = $form->getData();
        $this->save($assignation);

        return $assignation;
    }
}