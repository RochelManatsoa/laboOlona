<?php

namespace App\Repository\Moderateur;

use App\Entity\Moderateur\Metting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Metting>
 *
 * @method Metting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Metting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Metting[]    findAll()
 * @method Metting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Metting::class);
    }

//    /**
//     * @return Metting[] Returns an array of Metting objects
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

//    public function findOneBySomeField($value): ?Metting
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
