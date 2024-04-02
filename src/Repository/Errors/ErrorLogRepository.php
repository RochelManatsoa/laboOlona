<?php

namespace App\Repository\Errors;

use App\Entity\Errors\ErrorLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ErrorLog>
 *
 * @method ErrorLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ErrorLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ErrorLog[]    findAll()
 * @method ErrorLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ErrorLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ErrorLog::class);
    }

//    /**
//     * @return ErrorLog[] Returns an array of ErrorLog objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ErrorLog
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
