<?php

namespace App\Repository;

use App\Entity\Prestation;
use App\Data\V2\PrestationData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Prestation>
 *
 * @method Prestation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prestation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prestation[]    findAll()
 * @method Prestation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator,)
    {
        parent::__construct($registry, Prestation::class);
    }

    public function findPrestationsForReport()
    {
        $queryBuilder = $this->createQueryBuilder('p');
    
        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('p.status', ':statusValid'),
            $queryBuilder->expr()->eq('p.status', ':statusFeatured')
        );
    
        $isGeneratedConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('p.isGenerated', ':isGeneratedFalse'),
            $queryBuilder->expr()->isNull('p.isGenerated')
        );
    
        $query = $queryBuilder
            ->andWhere($isGeneratedConditions)
            ->andWhere($orConditions)
            ->setParameter('statusValid', Prestation::STATUS_VALID)
            ->setParameter('statusFeatured', Prestation::STATUS_FEATURED)
            ->setParameter('isGeneratedFalse', false)
            ->setMaxResults(10)
            ->orderBy('p.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }

    public function findValidPrestationsElastic()
    {
        $queryBuilder = $this->createQueryBuilder('p');
    
        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('p.status', ':statusValid'),
            $queryBuilder->expr()->eq('p.status', ':statusFeatured')
        );
    
        $query = $queryBuilder
            ->andWhere($orConditions)
            ->setParameter('statusValid', Prestation::STATUS_VALID)
            ->setParameter('statusFeatured', Prestation::STATUS_FEATURED)
            ->orderBy('p.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findStatusValid()
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.isGenerated = :isGenerated')
            ->andWhere('p.status = :statusValid')
            ->setParameter('statusValid', Prestation::STATUS_VALID)
            ->setParameter('isGenerated', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findStatusPremium()
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.isGenerated = :isGenerated')
            ->andWhere('p.status = :statusFeatured')
            ->setParameter('statusFeatured', Prestation::STATUS_FEATURED)
            ->setParameter('isGenerated', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }

    public function findSearch(PrestationData $searchData): PaginationInterface
    {
        $qb = $this
            ->createQueryBuilder('p')
            ->select('p', 'c', 'e')
            ->leftJoin('p.candidateProfile', 'c')
            ->leftJoin('p.entrepriseProfile', 'e')
            ->where('p.status != :status')
            ->setParameter('status', Prestation::STATUS_DELETED)
            ->orderBy('p.id', 'DESC');

        if (!empty($searchData->q)) {
            $words = explode(' ', $searchData->q);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $qb->andWhere('p.titre LIKE :word OR p.description LIKE :word OR p.openai LIKE :word')
                        ->setParameter('word', "%{$word}%");
                }
            }
        }

        if (!empty($searchData->candidat)) {
            $qb = $qb
                ->andWhere('c.id = :candidat')
                ->setParameter('candidat', "{$searchData->candidat->getId()}");
        }

        if (!empty($searchData->entreprise)) {
            $qb = $qb
                ->andWhere('e.id = :entreprise')
                ->setParameter('entreprise', "{$searchData->entreprise->getId()}");
        }

        if (!empty($searchData->status)) {
            $qb = $qb
                ->andWhere('p.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%");
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
