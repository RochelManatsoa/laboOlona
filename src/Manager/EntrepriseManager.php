<?php

namespace App\Manager;

use App\Entity\Candidate\Competences;
use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use App\Entity\Langue;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class EntrepriseManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserService $userService
    ){}
    
    public function findValidCandidats(): array
    {
        return $this->candidateProfileRepository->findBy(
            ['status' => CandidateProfile::STATUS_VALID],
            ['id' => 'DESC']
        );
    }

    public function findAllAnnonces(?string $titre = null, ?string $status = null, ?string $typeContrat = null, ?string $salaire = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];
        $conditions = [];

        if($titre == null && $status == null && $typeContrat == null && $salaire == null){
            return $this->jobListingRepository->findBy(
                ['entreprise' => $user->getEntrepriseProfile()],
                ['id' => 'DESC']
            );
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(t.nom LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($salaire)) {
            $conditions[] = '(j.salaire LIKE :salaire )';
            $parameters['salaire'] = '%' . $salaire . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.typeContrat', 't')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('j.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllCandidature(?string $titre = null, ?string $candidat = null, ?string $status = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];

        $conditions = [];
        
        if($titre == null && $status == null && $candidat == null){
            $annoncesGroupees = [];
            foreach ($user->getEntrepriseProfile()->getAllApplications() as $candidature) {
                $annonce = $candidature->getAnnonce();
                $annonceId = $annonce->getId();
            
                if (!array_key_exists($annonceId, $annoncesGroupees)) {
                    $annoncesGroupees[$annonceId] = [
                        'annonce' => $annonce,
                        'candidatures' => [],
                        'assignations' => [],
                    ];
                }
            
                $annoncesGroupees[$annonceId]['candidatures'][] = $candidature;
            
                foreach ($annonce->getAssignations() as $assignation) {
                    $annoncesGroupees[$annonceId]['assignations'][$assignation->getId()] = $assignation;
                }
            }
            
            
            return $annoncesGroupees;
            // return $user->getEntrepriseProfile()->getAllApplications();
        }
        
        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($candidat)) {
            $conditions[] = '(c.nom LIKE :candidat OR c.prenom LIKE :candidat OR c.email LIKE :candidat )';
            $parameters['candidat'] = '%' . $candidat . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(a.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }
        $qb->select('a')
            ->from('App\Entity\Candidate\Applications', 'a')
            ->leftJoin('a.annonce', 'j')
            ->leftJoin('a.candidat', 'c')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('a.id', 'DESC')
            ->setParameters($parameters);
        
        // return $qb->getQuery()->getResult();
        
        $data = $qb->getQuery()->getResult();
        $annoncesGroupees = [];

        foreach ($data as $candidature) {
            $annonceId = $candidature->getAnnonce()->getId();
            if (!array_key_exists($annonceId, $annoncesGroupees)) {
                $annoncesGroupees[$annonceId] = [
                    'annonce' => $candidature->getAnnonce(),
                    'candidatures' => []
                ];
            }
            $annoncesGroupees[$annonceId]['candidatures'][] = $candidature;
        }
        return $annoncesGroupees;
    }
    
    
    public function filter(?array $secteurs, ?array $titres, ?array $competences, ?array $langues): array
    {
        // Vérifie si tous les tableaux sont vides
        if (empty($secteurs) && empty($titres) && empty($competences) && empty($langues)) {
            return $this->candidateProfileRepository->findBy(
                ['status' => CandidateProfile::STATUS_VALID],
                ['id' => 'DESC']
            );
        }
        
        $qb = $this->em->createQueryBuilder();

        // Construction de la requête de base
        $qb->select('c')
        ->from('App\Entity\CandidateProfile', 'c')
        ->leftJoin('c.secteurs', 's')
        ->leftJoin('c.langages', 'l')
        ->leftJoin('l.langue', 'lang')
        ->leftJoin('c.competences', 'skill');

        // Ajout de conditions basées sur les valeurs non null
        if (!empty($titres)) {
            $qb->andWhere('c.titre IN (:titres)')
            ->setParameter('titres', $titres);
        }

        if (!empty($competences)) {
            $qb->andWhere('skill.id IN (:competences)')
            ->setParameter('competences', $competences);
        }

        if (!empty($langues)) {
            $qb->andWhere('lang.id IN (:langues)')
            ->setParameter('langues', $langues);
        }

        if (!empty($secteurs)) {
            $qb->andWhere('s.id IN (:secteurs)')
            ->setParameter('secteurs', $secteurs);
        }

        // Ajout du tri
        $qb->orderBy('c.id', 'DESC');
        
        return $qb->getQuery()->getResult();
    }


}
