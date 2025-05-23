<?php

namespace App\Repository\Vues;

use App\Entity\Vues\VideoVues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VideoVues>
 *
 * @method VideoVues|null find($id, $lockMode = null, $lockVersion = null)
 * @method VideoVues|null findOneBy(array $criteria, array $orderBy = null)
 * @method VideoVues[]    findAll()
 * @method VideoVues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VideoVuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoVues::class);
    }

    public function findByVideoAndUser($videoId, $userId)
    {
        return $this->createQueryBuilder('vv')
            ->where('vv.video = :videoId')
            ->andWhere('vv.user = :userId')
            ->setParameter('videoId', $videoId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return VideoVues[] Returns an array of VideoVues objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VideoVues
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
