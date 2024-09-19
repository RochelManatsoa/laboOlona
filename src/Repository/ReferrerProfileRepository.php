<?php

namespace App\Repository;

use App\Entity\ReferrerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReferrerProfile>
 *
 * @method ReferrerProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReferrerProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReferrerProfile[]    findAll()
 * @method ReferrerProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferrerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferrerProfile::class);
    }

//    /**
//     * @return ReferrerProfile[] Returns an array of ReferrerProfile objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ReferrerProfile
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
