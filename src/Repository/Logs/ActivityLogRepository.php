<?php

namespace App\Repository\Logs;

use App\Entity\Logs\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 *
 * @method ActivityLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityLog[]    findAll()
 * @method ActivityLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

   /**
    * @return ActivityLog[] Returns an array of ActivityLog objects
    */
   public function findUserLogs($user): array
   {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.level < :level')
            ->setParameter('user', $user)
            ->setParameter('level', ActivityLog::LEVEL_WARNING)
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
        ;
   }

//    public function findOneBySomeField($value): ?ActivityLog
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
