<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Boost;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\BusinessModel\BoostVisibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<BoostVisibility>
 *
 * @method BoostVisibility|null find($id, $lockMode = null, $lockVersion = null)
 * @method BoostVisibility|null findOneBy(array $criteria, array $orderBy = null)
 * @method BoostVisibility[]    findAll()
 * @method BoostVisibility[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoostVisibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoostVisibility::class);
    }

    public function findLatestBoostVisibilityByBoost(Boost $boost)
    {
        return $this->createQueryBuilder('bv')
            ->andWhere('bv.boost = :boost')
            ->setParameter('boost', $boost)
            ->orderBy('bv.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
