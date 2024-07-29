<?php

namespace App\Repository;

use App\Entity\Prestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prestation::class);
    }

    public function findJoblistingsForReport()
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
}
