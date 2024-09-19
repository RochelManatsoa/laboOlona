<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\BoostVisibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

//    /**
//     * @return BoostVisibility[] Returns an array of BoostVisibility objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BoostVisibility
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
