<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\CandidateProfile;
use App\Data\Profile\CandidatSearchData;
use Doctrine\Persistence\ManagerRegistry;
use App\Data\Profile\RecrutementSearchData;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<CandidateProfile>
 *
 * @method CandidateProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method CandidateProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method CandidateProfile[]    findAll()
 * @method CandidateProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandidateProfileRepository extends ServiceEntityRepository
{

    public function __construct(
        ManagerRegistry $registry, 
        private PaginatorInterface $paginator,
    )
    {
        parent::__construct($registry, CandidateProfile::class);
    }

    /**
     * @return Expert[] Returns an array of Expert objects
     */
    public function findTopExperts(string $value = "", int $max = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.fileName <> :defaultAvatar')
            ->andWhere('c.status = :statusValid')
            ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
            ->setParameter('defaultAvatar', 'avatar-default.jpg')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($max)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findTopRanked(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c, COUNT(v.id) as HIDDEN num_views')
            ->leftJoin('c.vues', 'v')
            ->andWhere('c.fileName <> :defaultAvatar')
            ->andWhere('c.status = :statusFeatured')
            ->setParameter('statusFeatured', CandidateProfile::STATUS_FEATURED)
            ->setParameter('defaultAvatar', 'avatar-default.jpg')
            ->groupBy('c')
            ->orderBy('num_views', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByIdDesc()
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySecteur($secteurId)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.secteurs', 's')
            ->where('s.id = :secteurId')
            ->andWhere('c.fileName <> :defaultAvatar')
            ->andWhere('c.status = :statusValid')
            ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
            ->setParameter('defaultAvatar', 'avatar-default.jpg')
            ->setParameter('secteurId', $secteurId)
            ->getQuery()
            ->getResult();
    }
    
    public function findAllValid()
    {
        $queryBuilder = $this->createQueryBuilder('c');

        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('c.status', ':statusValid'),
            $queryBuilder->expr()->eq('c.status', ':statusFeatured')
        );

        $query = $queryBuilder
            ->andWhere('c.fileName <> :defaultAvatar')
            ->andWhere('c.isGeneretated = :isGenerated')
            ->andWhere($orConditions)
            ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
            ->setParameter('statusFeatured', CandidateProfile::STATUS_FEATURED)
            ->setParameter('defaultAvatar', 'avatar-default.jpg')
            ->setParameter('isGenerated', true)
            ->orderBy('c.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findStatusValid()
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.isGeneretated = :isGenerated')
            ->andWhere('c.status = :statusValid')
            ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
            ->setParameter('isGenerated', true)
            ->orderBy('c.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findStatusPremium()
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.isGeneretated = :isGenerated')
            ->andWhere('c.status = :statusFeatured')
            ->setParameter('statusFeatured', CandidateProfile::STATUS_FEATURED)
            ->setParameter('isGenerated', true)
            ->orderBy('c.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }

    public function findUniqueTitlesBySecteurs($secteurs)
    {
        $qb = $this->createQueryBuilder('cp')
            ->select('DISTINCT cp.titre , cp.id')
            ->leftJoin('cp.secteurs', 's')
            ->where('s.id IN (:secteurs)')
            ->setParameter('secteurs', $secteurs);

        return $qb->getQuery()->getResult();
    }


    public function findSearch(CandidatSearchData $searchData): PaginationInterface
    {
        $qb = $this
            ->createQueryBuilder('c')
            ->select('c, c.id AS matricule, c.relanceCount AS level, u.id, u.nom, COUNT(DISTINCT s.id) AS nombreDeCompetences, COUNT(DISTINCT e.id) AS nombreDeExperiences, COUNT(DISTINCT n.id) AS nombreDeRelance')
            ->leftJoin('c.competences', 's')
            ->leftJoin('c.experiences', 'e')
            ->leftJoin('c.secteurs', 'sect')
            ->leftJoin('c.tarifCandidat', 't')
            ->leftJoin('c.cvs', 'cv')
            ->leftJoin('c.availability', 'dispo')
            ->join('c.candidat', 'u')
            ->leftJoin('u.recus', 'n')
            ->groupBy('u.id')
            ->orderBy('c.id', 'DESC');


        if (!empty($searchData->q)) {
            $words = explode(' ', $searchData->q);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $qb->andWhere('u.nom LIKE :word OR u.prenom LIKE :word OR u.email LIKE :word OR c.titre LIKE :word')
                        ->setParameter('word', "%{$word}%");
                }
            }
        }

        if (!empty($searchData->status)) {
            $qb = $qb
                ->andWhere('c.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%");
        }

        if ($searchData->cv === 1) {
            $qb = $qb
                ->andWhere('cv.id IS NOT NULL');
        } elseif ($searchData->cv === 0) {
            $qb = $qb
                ->andWhere('cv.id IS NULL');
        }

        if ($searchData->avatar === 1) {
            $qb = $qb
                ->andWhere('c.fileName IS NOT NULL');
        } elseif ($searchData->avatar === 0) {
            $qb = $qb
                ->andWhere('c.fileName IS NULL');
        }

        if ($searchData->resume === 1) {
            $qb = $qb
                ->andWhere('c.resume IS NOT NULL');
        } elseif ($searchData->resume === 0) {
            $qb = $qb
                ->andWhere('c.resume IS NULL');
        }

        if ($searchData->tarif === 1) {
            $qb = $qb
                ->andWhere('t.id IS NOT NULL');
        } elseif ($searchData->tarif === 0) {
            $qb = $qb
                ->andWhere('t.id IS NULL');
        }

        if ($searchData->relance === 1) {
            $qb = $qb
                ->andWhere('n.id IS NOT NULL');
        } elseif ($searchData->relance === 0) {
            $qb = $qb
                ->andWhere('n.id IS NULL');
        }

        if ($searchData->dispo === 1) {
            $qb->andWhere('dispo.id IS NOT NULL');
        } elseif ($searchData->dispo === 0) {
            $qb->andWhere('dispo.id IS NULL');
        }

        if ($searchData->competences === 1) {
            $qb = $qb
                ->andWhere('s.id IS NOT NULL');
        } elseif ($searchData->competences === 0) {
            $qb = $qb
                ->andWhere('c.competences IS EMPTY');
        }

        if ($searchData->secteurs === 1) {
            $qb = $qb
                ->andWhere('sect.id IS NOT NULL');
        } elseif ($searchData->secteurs === 0) {
            $qb = $qb
                ->andWhere('c.secteurs IS EMPTY');
        }

        if ($searchData->experiences === 1) {
            $qb = $qb
                ->andWhere('e.id IS NOT NULL');
        } elseif ($searchData->experiences === 0) {
            $qb = $qb
                ->andWhere('c.experiences IS EMPTY');
        }

        if (!empty($searchData->matricule)) {
            $qb = $qb
                ->andWhere('c.id LIKE :id')
                ->setParameter('id', "%{$searchData->matricule}%");
        }

        if (!empty($searchData->level)) {
            $qb = $qb
                ->andWhere('c.relanceCount LIKE :relanceCount')
                ->setParameter('relanceCount', "%{$searchData->level}%");
        }

        $query =  $qb->getQuery();
        // dd($query->getResult());

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }

    public function findRecrutSearch(RecrutementSearchData $searchData): PaginationInterface
    {

        if (empty($searchData->q)) {
            return $this->paginator->paginate(
                [], // tableau vide pour représenter aucun résultat
                $searchData->page,
                20
            );
        }
        $queryString = $searchData->q;

        $qb = $this
            ->createQueryBuilder('c')
            ->select('c, c.id AS matricule, c.relanceCount AS level, u.id, u.nom, 
                COUNT(DISTINCT s.id) AS nombreDeCompetences, 
                COUNT(DISTINCT e.id) AS nombreDeExperiences, 
                COUNT(DISTINCT n.id) AS nombreDeRelance,
                COUNT(DISTINCT vues.id) AS nombreDeVues,
                COUNT(DISTINCT favoris.id) AS nombreDeFavoris,
                (CASE WHEN ' . implode(' AND ', array_map(function($word) {
                    return "c.titre LIKE '%" . $word . "%'";
                }, explode(' ', $queryString))) . ' THEN 1 ELSE 0 END) AS fullMatchCount')
            ->leftJoin('c.competences', 's')
            ->leftJoin('c.experiences', 'e')
            ->leftJoin('c.secteurs', 'sect')
            ->leftJoin('c.tarifCandidat', 't')
            ->leftJoin('c.cvs', 'cv')
            ->leftJoin('c.availability', 'dispo')
            ->join('c.candidat', 'u')
            ->leftJoin('u.recus', 'n')
            ->leftJoin('c.vues', 'vues')
            ->leftJoin('c.favoris', 'favoris')
            ->andWhere('c.status = :status')
            ->setParameter('status', CandidateProfile::STATUS_VALID)
            ->groupBy('u.id')
            ;

        $words = [];

        $orX = $qb->expr()->orX();
        $matchCountExpr = '0';

        foreach ($words as $index => $word) {
            $word = trim($word);
            if (!empty($word)) {
                $parameterName = ":word{$index}";
                $orX->add($qb->expr()->orX(
                    $qb->expr()->like('u.nom', $parameterName),
                    $qb->expr()->like('u.prenom', $parameterName),
                    $qb->expr()->like('u.email', $parameterName),
                    $qb->expr()->like('c.titre', $parameterName),
                    $qb->expr()->like('s.nom', $parameterName),
                    $qb->expr()->like('e.description', $parameterName),
                    $qb->expr()->like('sect.nom', $parameterName),
                    $qb->expr()->like('t.typeTarif', $parameterName),
                    $qb->expr()->like('c.technologies', $parameterName),
                    $qb->expr()->like('c.tools', $parameterName),
                    $qb->expr()->like('c.traductionEn', $parameterName),
                    $qb->expr()->like('c.resultPremium', $parameterName),
                    $qb->expr()->like('c.tesseractResult', $parameterName),
                    $qb->expr()->like('c.resultFree', $parameterName),
                    $qb->expr()->like('dispo.nom', $parameterName)
                ));
                $qb->setParameter($parameterName, "%{$word}%");

                // Ajoutez une condition CASE WHEN pour compter les correspondances
                $matchCountExpr .= " + (CASE WHEN u.nom LIKE {$parameterName} OR u.prenom LIKE {$parameterName} OR u.email LIKE {$parameterName} OR c.titre LIKE {$parameterName} OR s.nom LIKE {$parameterName} OR e.description LIKE {$parameterName} OR sect.nom LIKE {$parameterName} OR t.typeTarif LIKE {$parameterName} OR c.technologies LIKE {$parameterName} OR c.tools LIKE {$parameterName} OR c.traductionEn LIKE {$parameterName} OR c.resultPremium LIKE {$parameterName} OR c.tesseractResult LIKE {$parameterName} OR c.resultFree LIKE {$parameterName} OR dispo.nom LIKE {$parameterName} THEN 1 ELSE 0 END)";
            }
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX);
        }

        $qb->addSelect("({$matchCountExpr}) AS matchCount")
        ->orderBy('fullMatchCount', 'DESC') // Priorité aux correspondances complètes
        ->addOrderBy('matchCount', 'DESC') // Puis aux correspondances partielles
        ->addOrderBy('nombreDeVues', 'DESC')
        ->addOrderBy('nombreDeFavoris', 'DESC');

        $query = $qb->getQuery();

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }


    public function findEngineSearch(SearchData $searchData): PaginationInterface
    {
        if (empty($searchData->q)) {
            return $this->paginator->paginate(
                [], // tableau vide pour représenter aucun résultat
                $searchData->page,
                20
            );
        }
        $queryString = $searchData->q;

        $qb = $this
            ->createQueryBuilder('c')
            ->select('c, c.id AS matricule, c.relanceCount AS level, u.id, u.nom, 
                COUNT(DISTINCT s.id) AS nombreDeCompetences, 
                COUNT(DISTINCT e.id) AS nombreDeExperiences, 
                COUNT(DISTINCT n.id) AS nombreDeRelance,
                COUNT(DISTINCT vues.id) AS nombreDeVues,
                COUNT(DISTINCT favoris.id) AS nombreDeFavoris,
                (CASE WHEN ' . implode(' AND ', array_map(function($word) {
                    return "c.titre LIKE '%" . $word . "%'";
                }, explode(' ', $queryString))) . ' THEN 1 ELSE 0 END) AS fullMatchCount')
            ->leftJoin('c.competences', 's')
            ->leftJoin('c.experiences', 'e')
            ->leftJoin('c.secteurs', 'sect')
            ->leftJoin('c.tarifCandidat', 't')
            ->leftJoin('c.cvs', 'cv')
            ->leftJoin('c.availability', 'dispo')
            ->join('c.candidat', 'u')
            ->leftJoin('u.recus', 'n')
            ->leftJoin('c.vues', 'vues')
            ->leftJoin('c.favoris', 'favoris')
            ->andWhere('c.status = :status')
            ->setParameter('status', CandidateProfile::STATUS_VALID)
            ->groupBy('u.id')
            ;

        $words = [];

        $orX = $qb->expr()->orX();
        $matchCountExpr = '0';

        foreach ($words as $index => $word) {
            $word = trim($word);
            if (!empty($word)) {
                $parameterName = ":word{$index}";
                $orX->add($qb->expr()->orX(
                    $qb->expr()->like('u.nom', $parameterName),
                    $qb->expr()->like('u.prenom', $parameterName),
                    $qb->expr()->like('u.email', $parameterName),
                    $qb->expr()->like('c.titre', $parameterName),
                    $qb->expr()->like('s.nom', $parameterName),
                    $qb->expr()->like('e.description', $parameterName),
                    $qb->expr()->like('sect.nom', $parameterName),
                    $qb->expr()->like('t.typeTarif', $parameterName),
                    $qb->expr()->like('c.technologies', $parameterName),
                    $qb->expr()->like('c.tools', $parameterName),
                    $qb->expr()->like('c.traductionEn', $parameterName),
                    $qb->expr()->like('c.resultPremium', $parameterName),
                    $qb->expr()->like('c.tesseractResult', $parameterName),
                    $qb->expr()->like('c.resultFree', $parameterName),
                    $qb->expr()->like('dispo.nom', $parameterName)
                ));
                $qb->setParameter($parameterName, "%{$word}%");

                // Ajoutez une condition CASE WHEN pour compter les correspondances
                $matchCountExpr .= " + (CASE WHEN u.nom LIKE {$parameterName} OR u.prenom LIKE {$parameterName} OR u.email LIKE {$parameterName} OR c.titre LIKE {$parameterName} OR s.nom LIKE {$parameterName} OR e.description LIKE {$parameterName} OR sect.nom LIKE {$parameterName} OR t.typeTarif LIKE {$parameterName} OR c.technologies LIKE {$parameterName} OR c.tools LIKE {$parameterName} OR c.traductionEn LIKE {$parameterName} OR c.resultPremium LIKE {$parameterName} OR c.tesseractResult LIKE {$parameterName} OR c.resultFree LIKE {$parameterName} OR dispo.nom LIKE {$parameterName} THEN 1 ELSE 0 END)";
            }
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX);
        }

        $qb->addSelect("({$matchCountExpr}) AS matchCount")
        ->orderBy('fullMatchCount', 'DESC') // Priorité aux correspondances complètes
        ->addOrderBy('matchCount', 'DESC') // Puis aux correspondances partielles
        ->addOrderBy('nombreDeVues', 'DESC')
        ->addOrderBy('nombreDeFavoris', 'DESC');

        $query = $qb->getQuery();

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }

    public function findProfilesToRelance(int $daysSinceCreation, int $daysSinceLastRelance, int $relanceNumber)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.competences', 'comps')
            ->leftJoin('c.experiences', 'exps')
            ->leftJoin('c.cvs', 'cvs')
            ->leftJoin('c.tarifCandidat', 'tarif')
            ->join('c.candidat', 'u')
            ->where('comps.id IS NULL')
            ->andWhere('exps.id IS NULL')
            ->andWhere('c.fileName IS NULL')
            ->andWhere('cvs.id IS NULL')
            ->andWhere('tarif.id IS NULL')
            ->andWhere('c.createdAt <= :createdAt')
            ->andWhere('c.relanceCount = :relanceCount')
            ->setParameter('createdAt', new \DateTime('-' . $daysSinceCreation . ' days'))
            ->setParameter('relanceCount', $relanceNumber - 1);
        if ($relanceNumber === 0) {
            $qb->leftJoin('u.recus', 'n')
                ->andWhere('n.id IS NULL');
        }

        if ($daysSinceLastRelance > 0) {
            $qb->andWhere('c.relancedAt <= :lastRelanceAt')
                ->setParameter('lastRelanceAt', new \DateTime('-' . $daysSinceLastRelance . ' days'));
        }

        return $qb->getQuery()->getResult();
    }
    public function findProfilesForReport()
    {
        $queryBuilder = $this->createQueryBuilder('c');

        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('c.status', ':statusValid'),
            $queryBuilder->expr()->eq('c.status', ':statusFeatured')
        );

        $query = $queryBuilder
            ->andWhere('c.isGeneretated = :isGenerated')
            ->andWhere($orConditions)
            ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
            ->setParameter('statusFeatured', CandidateProfile::STATUS_FEATURED)
            ->setParameter('isGenerated', false)
            ->setMaxResults(5)
            ->orderBy('c.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }

    public function findProfilesForDictionary()
    {
        return $this->createQueryBuilder('cp')
            ->where('cp.isGeneretated = :isGeneretated')
            ->setParameter('isGeneretated', true)
            ->getQuery()
            ->getResult();
    }
}
