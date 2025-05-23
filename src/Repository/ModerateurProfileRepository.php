<?php

namespace App\Repository;

use App\Entity\ModerateurProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModerateurProfile>
 *
 * @method ModerateurProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModerateurProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModerateurProfile[]    findAll()
 * @method ModerateurProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModerateurProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModerateurProfile::class);
    }

//    /**
//     * @return ModerateurProfile[] Returns an array of ModerateurProfile objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ModerateurProfile
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
