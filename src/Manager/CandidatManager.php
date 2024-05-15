<?php

namespace App\Manager;

use App\Entity\Availability;
use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CandidatManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private JobListingRepository $jobListingRepository,
        private ApplicationsRepository $applicationsRepository,
        private MettingRepository $mettingRepository,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
        private UserService $userService
    ){}

    
    public function annoncesCandidatDefaut(CandidateProfile $candidat): array
    {
        return array_slice(array_merge(
            $this->getPostingsByCandidatSectors($candidat),
            $this->getPostingsByCandidatSkills($candidat),
            $this->getPostingsByCandidatLocalisation($candidat)
        ), 0, 6);
    }
    
    public function findExpertAnnouncements(CandidateProfile $candidat): array
    {
        return array_slice(array_merge(
            $this->getPostingsByCandidatSkills($candidat),
            $this->getPostingsByCandidatLocalisation($candidat),
            $this->getPostingsByCandidatSectors($candidat)
        ), 0, 6);
    }
    
    public function getPostingsByCandidatSectors(CandidateProfile $candidat): array
    {
        $annonces = [];
        $sectors = $candidat->getSecteurs();
        foreach ($sectors as $sector) {
            $sectorEntreprises = $sector->getJobListings();
            foreach ($sectorEntreprises as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getPostingsByCandidatLocalisation(CandidateProfile $candidat): array
    {
        $annonces = [];

        $entreprise = $this->entrepriseProfileRepository->findBy([
            'localisation' => $candidat->getLocalisation()
        ]);

        foreach ($entreprise as $company) {
            $companyPostings = $company->getJobListings();
            foreach ($companyPostings as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getPostingsByCandidatSkills(CandidateProfile $candidat): array
    {
        $annonces = [];
        $skills = $candidat->getCompetences();
        foreach ($skills as $skill) {
            $skillPostings = $skill->getJobListings();
            foreach ($skillPostings as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getAll(): array
    {
        $queryBuilder = $this->candidateProfileRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->getQuery();
            
        return $queryBuilder->getResult();
    }

    public function searchAnnonce(?string $titre = null, ?string $lieu = null, ?string $typeContrat = null, ?string $competences = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($titre == null && $lieu == null && $typeContrat == null && $competences == null){
            return $this->jobListingRepository->findAllJobListingPublished();
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(t.nom LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($competences)) {
            $conditions[] = '(c.nom LIKE :competences )';
            $parameters['competences'] = '%' . $competences . '%';
        }

        if (!empty($lieu)) {
            $conditions[] = '(j.lieu LIKE :lieu )';
            $parameters['lieu'] = '%' . $lieu . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.competences', 'c')
            ->leftJoin('j.typeContrat', 't')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function getPendingApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_PENDING
        ]);
    }

    public function getAcceptedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_ACCEPTED
        ]);
    }

    public function getRefusedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_REJECTED
        ]);
    }

    public function getArchivedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_ARCHIVED
        ]);
    }

    public function getMettingApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_METTING
        ]);
    }

    public function initAvailability(CandidateProfile $candidat): Availability
    {
        $availability = $candidat->getAvailability();
        if(!$availability instanceof Availability){
            $availability = new Availability();
            $availability->addCandidat($candidat);
        }

        return $availability;
    }
    
    public function sendNotificationEmail($candidat) {
        $candidat->setStatus(CandidateProfile::STATUS_PENDING);
        $this->em->persist($candidat);
        $this->em->flush();
        $this->mailerService->sendMultiple(
            $this->moderateurManager->getModerateurEmails(),
            $candidat->getCandidat()->getPrenom().' a mis à jour son profil sur Olona Talents',
            "moderateur/notification_update_profile.html.twig",
            [
                'user' => $candidat->getCandidat(),
                'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidat_view', ['id' => $candidat->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    public function getExperiencesSortedByDate($candidat) {
        $experiences = $candidat->getExperiences()->toArray(); // Convertir en tableau
        
        // Utiliser une fonction anonyme comme callback
        usort($experiences, function ($experience1, $experience2) {
            $dateDebut1 = $experience1->getDateDebut();
            $dateDebut2 = $experience2->getDateDebut();
            
            if ($dateDebut1 == $dateDebut2) {
                return 0;
            }
            
            return ($dateDebut1 < $dateDebut2) ? 1 : -1;
        });
        
        return $experiences;
    }
    
    public function getCompetencesSortedByNote($candidat) {
        $competences = $candidat->getCompetences()->toArray(); // Convertir en tableau
        
        // Utiliser une fonction anonyme comme callback pour trier par note (du plus haut au plus bas)
        usort($competences, function ($competence1, $competence2) {
            $note1 = $competence1->getNote();
            $note2 = $competence2->getNote();
            
            if ($note1 == $note2) {
                return 0;
            }
            
            return ($note1 < $note2) ? 1 : -1;
        });
        
        return $competences;
    }
    
    public function getLangagesSortedByNiveau($candidat) {
        $langages = $candidat->getLangages()->toArray(); // Convertir en tableau
        
        // Utiliser une fonction anonyme comme callback pour trier par niveau (du plus haut au plus bas)
        usort($langages, function ($langage1, $langage2) {
            $niveau1 = $langage1->getNiveau();
            $niveau2 = $langage2->getNiveau();
            
            if ($niveau1 == $niveau2) {
                return 0;
            }
            
            return ($niveau1 < $niveau2) ? 1 : -1;
        });
        
        return $langages;
    }
}
