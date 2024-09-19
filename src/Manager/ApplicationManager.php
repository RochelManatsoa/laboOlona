<?php

namespace App\Manager;

use App\Entity\Candidate\Applications;
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
use App\Repository\Moderateur\AssignationRepository;
use Doctrine\ORM\Query\Expr\Func;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApplicationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private JobListingRepository $jobListingRepository,
        private AssignationRepository $assignationRepository,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private UserService $userService
    ){}

    public function init(): Applications
    {
        $application = new Applications();
        $application->setDateCandidature(new \DateTime());
        $application->setStatus(Applications::STATUS_PENDING);
        
        return $application;
    }

    public function save(Applications $application)
    {
        $this->em->persist($application);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $application = $form->getData();
        $this->save($application);

        return $application;
    }
}