<?php

namespace App\Repository;

use App\Entity\CandidateProfile;
use App\Data\Profile\CandidatSearchData;
use Doctrine\Persistence\ManagerRegistry;
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
    
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
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
             ->getResult()
        ;
    }

    public function findTopRanked() : array
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
             ->getResult()
         ;

     }
     
     public function findAllOrderedByIdDesc()
     {
         return $this->createQueryBuilder('j')
             ->orderBy('j.id', 'DESC')
             ->getQuery()
             ->getResult();
     }

     public function findBySecteur($secteurId) {
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

    public function findAllValid() {
        $query = $this->createQueryBuilder('c')
             ->andWhere('c.fileName <> :defaultAvatar') 
             ->andWhere('c.status = :statusValid') 
             ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
             ->setParameter('defaultAvatar', 'avatar-default.jpg')
             ->orderBy('c.id', 'DESC')
             ->getQuery()
        ;
        return $query->getResult();

        // return $this->paginator->paginate(
        //     $query,
        //     1,
        //     8
        // );
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
        ->select('c, c.id AS matricule, u.id, u.nom, COUNT(DISTINCT s.id) AS nombreDeCompetences, COUNT(DISTINCT e.id) AS nombreDeExperiences, COUNT(DISTINCT n.id) AS nombreDeRelance')
        ->leftJoin('c.competences', 's')
        ->leftJoin('c.experiences', 'e')
        ->join('c.candidat', 'u')
        ->leftJoin('u.recus', 'n')
        ->groupBy('u.id')
        ->orderBy('c.id', 'DESC')
        ;

        
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

        if(!empty($searchData->status)){
            $qb = $qb
                ->andWhere('c.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%")
            ;
        }

        if ($searchData->cv === 1) {
            $qb = $qb
                ->andWhere('c.cv IS NOT NULL');
        } elseif ($searchData->cv === 0) {
            $qb = $qb
                ->andWhere('c.cv IS NULL');
        }

        if(!empty($searchData->matricule)){
            $qb = $qb
                ->andWhere('c.id LIKE :id')
                ->setParameter('id', "%{$searchData->matricule}%")
            ;
        }

        $query =  $qb->getQuery();
        // dd($query->getResult());

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            10
        );
    }
}
