<?php

namespace App\Repository\Cron;

use App\Entity\Cron\CronLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CronLog>
 *
 * @method CronLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method CronLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method CronLog[]    findAll()
 * @method CronLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CronLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronLog::class);
    }

//    /**
//     * @return CronLog[] Returns an array of CronLog objects
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

//    public function findOneBySomeField($value): ?CronLog
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
