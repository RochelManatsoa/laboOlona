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
            $qb = $this->jobListingRepository->createQueryBuilder('j');
            $qb
                ->where('j.entreprise = :entreprise')
                ->andWhere('j.status NOT IN (:statusExcluded)')
                ->setParameter('entreprise', $user->getEntrepriseProfile())
                ->setParameter('statusExcluded', ['DELETED', 'EXPIRED', 'RESERVED', 'UNPUBLISHED'])
                ->orderBy('j.id', 'DESC')
            ;

            return $qb->getQuery()->getResult();
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

    public function findAllCandidature(?string $titre = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];

        $conditions = [];
        
        if($titre == null){
            $annoncesGroupees = [];
            foreach ($user->getEntrepriseProfile()->getAllApplications() as $candidature) {
                $annonce = $candidature->getAnnonce();
                $annonceId = $annonce->getId();
            
                if (!array_key_exists($annonceId, $annoncesGroupees)) {
                    $annoncesGroupees[$annonceId] = [
                        'annonce' => $annonce,
                        'candidatures' => [],
                        'assignations' => [],
                        'suggestions' => [],
                        'forfait_min' => 0,
                    ];
                }
            
                $annoncesGroupees[$annonceId]['candidatures'][] = $candidature;
            
                foreach ($annonce->getAssignations() as $assignation) {
                    $annoncesGroupees[$annonceId]['assignations'][$assignation->getId()] = $assignation;
                    if($assignation->getApplication() === null){
                        $annoncesGroupees[$annonceId]['suggestions'][$assignation->getId()] = $assignation;
                    }
                }
            }
            
            foreach ($annoncesGroupees as $annonceId => &$annonceGroupee) {
                if (!empty($annonceGroupee['suggestions'])) {
                    // Initialisation avec une valeur très haute pour pouvoir la comparer.
                    $forfaitMin = PHP_INT_MAX;
            
                    foreach ($annonceGroupee['suggestions'] as $assignationId => $assignation) {
                        // Comparer le forfait de l'assignation actuelle avec le forfait minimum enregistré.
                        $forfaitActuel = $assignation->getForfait();
                        if ($forfaitActuel < $forfaitMin) {
                            $forfaitMin = $forfaitActuel;
                        }
                    }
            
                    // Mise à jour de forfait_min avec la valeur la plus basse trouvée.
                    $annonceGroupee['forfait_min'] = $forfaitMin;
                } else {
                    // S'il n'y a pas de suggestions, on peut définir forfait_min à 0 ou à une autre valeur par défaut.
                    $annonceGroupee['forfait_min'] = 0;
                }
            }
            unset($annonceGroupee);
            
            
            return $annoncesGroupees;
        }
        
        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        $qb->select('a')
            ->from('App\Entity\Candidate\Applications', 'a')
            ->leftJoin('a.annonce', 'j')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('a.id', 'DESC')
            ->setParameters($parameters);
        
        // return $qb->getQuery()->getResult();
        
        $data = $qb->getQuery()->getResult();
        $annoncesGroupees = [];

        foreach ($data as $candidature) {
            $annonce = $candidature->getAnnonce();
            $annonceId = $candidature->getAnnonce()->getId();
            if (!array_key_exists($annonceId, $annoncesGroupees)) {
                $annoncesGroupees[$annonceId] = [
                    'annonce' => $candidature->getAnnonce(),
                    'candidatures' => [],
                    'assignations' => [],
                    'suggestions' => [],
                    'forfait_min' => 0,
                ];
            }
            $annoncesGroupees[$annonceId]['candidatures'][] = $candidature;
            foreach ($annonce->getAssignations() as $assignation) {
                $annoncesGroupees[$annonceId]['assignations'][$assignation->getId()] = $assignation;
                if($assignation->getApplication() === null){
                    $annoncesGroupees[$annonceId]['suggestions'][$assignation->getId()] = $assignation;
                }
            }
            
            foreach ($annoncesGroupees as $annonceId => &$annonceGroupee) {
                if (!empty($annonceGroupee['suggestions'])) {
                    // Initialisation avec une valeur très haute pour pouvoir la comparer.
                    $forfaitMin = PHP_INT_MAX;
            
                    foreach ($annonceGroupee['suggestions'] as $assignationId => $assignation) {
                        // Comparer le forfait de l'assignation actuelle avec le forfait minimum enregistré.
                        $forfaitActuel = $assignation->getForfait();
                        if ($forfaitActuel < $forfaitMin) {
                            $forfaitMin = $forfaitActuel;
                        }
                    }
            
                    // Mise à jour de forfait_min avec la valeur la plus basse trouvée.
                    $annonceGroupee['forfait_min'] = $forfaitMin;
                } else {
                    // S'il n'y a pas de suggestions, on peut définir forfait_min à 0 ou à une autre valeur par défaut.
                    $annonceGroupee['forfait_min'] = 0;
                }
            }
            unset($annonceGroupee);
        }
        return $annoncesGroupees;
    }

    public function countElements(array $annoncesGroupees): array
    {
        $totalCandidatures = 0;
        $totalSuggestions = 0;

        foreach ($annoncesGroupees as $annonceId => $annonceGroupee) {
            // Compter le nombre de candidatures pour cette annonce.
            $totalCandidatures += count($annonceGroupee['candidatures']);

            // Compter le nombre de suggestions pour cette annonce.
            $totalSuggestions += count($annonceGroupee['suggestions']);
        }

        return [
            'candidatures' => $totalCandidatures,
            'suggestions' => $totalSuggestions,
        ];
    }
    
    
    public function filter(?array $secteurs, ?array $titres, ?array $competences, ?array $langues): array
    {
        // Vérifie si tous les tableaux sont vides
        if (empty($secteurs) && empty($titres) && empty($competences) && empty($langues)) {
            return $this->candidateProfileRepository->findAllValid();
        }
        
        $qb = $this->em->createQueryBuilder();

        // Construction de la requête de base
        $qb->select('c')
        ->from('App\Entity\CandidateProfile', 'c')
        ->where('c.status = :statusValid') // Ajoutez cette ligne pour filtrer par statut
        ->andWhere('c.fileName IS NOT NULL') 
        ->andWhere('c.fileName <> :defaultAvatar') 
        ->setParameter('defaultAvatar', 'avatar-default.jpg')
        ->setParameter('statusValid', CandidateProfile::STATUS_VALID) // Définition du paramètre pour le statut
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
    
    public function newfilter(?string $query): array
    {
        if ($query === null) {
            return $this->candidateProfileRepository->findAllValid();
        }
        
        $parameters = [
            'statusValid' => CandidateProfile::STATUS_VALID,
            'defaultAvatar' => 'avatar-default.jpg'
        ];
        $conditions = [
            'c.status = :statusValid',
            'c.fileName IS NOT NULL',
            'c.fileName <> :defaultAvatar'
        ];
        
        // Création du QueryBuilder
        $qb = $this->em->createQueryBuilder();
        
        // Construction de la requête de base
        $qb->select('c')
           ->from('App\Entity\CandidateProfile', 'c')
           ->leftJoin('c.competences', 'comp')
           ->leftJoin('c.secteurs', 'sect')
           ->leftJoin('c.experiences', 'exp'); // Assurez-vous que cette jointure est correcte selon votre logique métier
        
        // Traiter $query si elle n'est pas vide
        if (!empty($query)) {
            $words = explode(' ', $query);
            $searchConditions = [];
            
            foreach ($words as $index => $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $wordParam = "query$index"; // Création d'un identifiant de paramètre basé sur l'index du mot
                    // Condition pour rechercher le mot dans le titre, les compétences, les expériences et les secteurs
                    $searchConditions[] = "(c.titre LIKE :$wordParam OR comp.nom LIKE :$wordParam OR exp.nom LIKE :$wordParam OR sect.nom LIKE :$wordParam)";
                    $parameters[$wordParam] = '%' . $word . '%';
                }
            }
            
            if (!empty($searchConditions)) {
                // Ajoute les conditions de recherche dans le tableau des conditions
                $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
        }
        
        // Appliquer les conditions dynamiques
        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }
        
        // Appliquer les paramètres dynamiques
        foreach ($parameters as $key => $value) {
            $qb->setParameter($key, $value);
        }
        
        // Ajout du tri
        $qb->orderBy('c.id', 'DESC');
        
        // Exécution de la requête et retour des résultats
        return $qb->getQuery()->getResult();
    }
}
