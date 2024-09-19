<?php

namespace App\Repository\Vues;

use App\Entity\Vues\CandidatVues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CandidatVues>
 *
 * @method CandidatVues|null find($id, $lockMode = null, $lockVersion = null)
 * @method CandidatVues|null findOneBy(array $criteria, array $orderBy = null)
 * @method CandidatVues[]    findAll()
 * @method CandidatVues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandidatVuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidatVues::class);
    }

//    /**
//     * @return CandidatVues[] Returns an array of CandidatVues objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CandidatVues
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
