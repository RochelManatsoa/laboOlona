<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Avantage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avantage>
 *
 * @method Avantage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Avantage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Avantage[]    findAll()
 * @method Avantage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvantageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avantage::class);
    }

//    /**
//     * @return Avantage[] Returns an array of Avantage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Avantage
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
