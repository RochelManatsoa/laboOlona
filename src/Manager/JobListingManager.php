<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Finance\Devise;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Entity\Vues\AnnonceVues;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use App\Entity\Entreprise\JobListing;
use App\Entity\Candidate\Applications;
use App\Entity\Entreprise\BudgetAnnonce;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Repository\Candidate\ApplicationsRepository;
use App\Manager\BusinessModel\BoostVisibilityManager;
use App\Service\ActivityLogger;

class JobListingManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationsRepository $applicationsRepository,
        private EntrepriseManager $entrepriseManager,
        private CreditManager $creditManager,
        private ProfileManager $profileManager,
        private BoostVisibilityManager $boostVisibilityManager,
        private ActivityLogger $activityLogger,
    ){}

    public function init(EntrepriseProfile $recruiter): JobListing
    {
        $budget = $this->initBudgetAnnonce($this->entrepriseManager->getEntrepriseDevise($recruiter));
        $jobListing = new JobListing();
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setStatus(JobListing::STATUS_PENDING);
        $jobListing->setIsGenerated(false);
        $jobListing->setJobId(new Uuid(Uuid::v1()));
        $jobListing->setEntreprise($recruiter);
        $jobListing->setBudgetAnnonce($budget);
        $jobListing->setBoost(null);
        $jobListing->setBoostFacebook(null);

        return $jobListing;
    }

    public function initBudgetAnnonce(Devise $devise): BudgetAnnonce
    {
        $budget = new BudgetAnnonce();
        $budget->setCurrency($devise);

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

    public function getStatuses(): array
    {
        return [
            'Publiée' => JobListing::STATUS_PUBLISHED ,
            'En attente' => JobListing::STATUS_PENDING ,
            'Expirée' => JobListing::STATUS_EXPIRED ,
            'Effacée' => JobListing::STATUS_DELETED ,
            'Boostée' => JobListing::STATUS_FEATURED ,
        ];
    }
    
    public function handleJobListingSubmission(JobListing $jobListing, User $currentUser): array
    {
        if($currentUser->getEntrepriseProfile() instanceof EntrepriseProfile && $currentUser->getEntrepriseProfile()->isIsPremium() === true){
            $boostOption = $this->em->getRepository(Boost::class)->findOneBy(['type' => 'JOB_LISTING']);
            if($boostOption instanceof Boost){
                $visibilityBoost = $this->boostVisibilityManager->init($boostOption);
                $visibilityBoost = $this->boostVisibilityManager->update($visibilityBoost, $boostOption);
                $jobListing->setStatus(JobListing::STATUS_FEATURED);
                $jobListing->setBoost($boostOption);
                $jobListing->addBoostVisibility($visibilityBoost);
                $currentUser->addBoostVisibility($visibilityBoost);
                $this->em->persist($visibilityBoost);
                $this->em->flush();
                $this->activityLogger->logActivity($currentUser, ActivityLog::ACTIVITY_CREATE, 'Boost Offre d\'emploi sur Olona Talents', ActivityLog::LEVEL_INFO);
            }
            return [
                'success' => true, 
                'status' => 'Succès',
                'message' => "Boost effectué"
            ];
        }

        $boost = $jobListing->getBoost();
        
        $responseBoost = ['success' => true];
        $responseDefault = ['success' => true];
        
        $hasBoost = $boost instanceof Boost;
        
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_OFFER);
        if ($this->profileManager->canBuy($currentUser, $creditAmount)) {
            $responseDefault = $this->creditManager->adjustCredits($currentUser, $creditAmount, "Publication annonce");
        } else {
            return [
                'success' => false, 
                'status' => 'Echec',
                'message' => "Crédits insuffisants pour publier une annonce"
            ];
        }

        // Vérification et ajustement des crédits pour le Boost standard
        if ($hasBoost) {
            if ($this->profileManager->canBuy($currentUser, $boost->getCredit())) {
                $responseBoost = $this->creditManager->adjustCredits($currentUser, $boost->getCredit(), "Boost Offre d\'emploi sur Olona Talents");
            } else {
                return [
                    'success' => false, 
                    'status' => 'Echec',
                    'message' => "Crédits insuffisants pour ce boost"
                ];
            }
        }

        // Si tous les ajustements de crédits ont réussi, ou si aucun boost n'a été pris, on valide l'opération
        if ($responseBoost['success'] && $responseDefault['success']) {
            return [
                'success' => true, 
                'status' => 'Succès',
                'message' => "Boost effectué"
            ];
        }

        // Cas de crédits insuffisants non gérés spécifiquement
        return [
            'success' => false,
            'status' => 'Echec',
            'message' => "Crédits insuffisants pour les opérations demandées"
        ];
    }
}