<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\TransactionReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionReference>
 *
 * @method TransactionReference|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionReference|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionReference[]    findAll()
 * @method TransactionReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionReference::class);
    }

//    /**
//     * @return TransactionReference[] Returns an array of TransactionReference objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TransactionReference
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
