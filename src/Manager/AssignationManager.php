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
use App\Repository\Moderateur\AssignationRepository;
use Doctrine\ORM\Query\Expr\Func;
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
        private AssignationRepository $assignationRepository,
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

    public function getGroupedBy(string $status){
        $assignations = $this->assignationRepository->findBy([
            'status' => $status
        ], ['id' => 'DESC']);
        
        $assignationsByJobListing = [];

        foreach ($assignations as $assignation) {
            $jobListing = $assignation->getJobListing();
            if ($jobListing) {
                $jobListingId = $jobListing->getId();
                if (!array_key_exists($jobListingId, $assignationsByJobListing)) {
                    $assignationsByJobListing[$jobListingId] = [
                        'jobListing' => $jobListing,
                        'assignations' => []
                    ];
                }
                $assignationsByJobListing[$jobListingId]['assignations'][] = $assignation;
            }
        }
        
        // Si vous avez besoin d'un tableau d'annonces sans les clés d'identification,
        // vous pouvez utiliser array_values pour réindexer le tableau
        return $assignationsByJobListing;
    }
}