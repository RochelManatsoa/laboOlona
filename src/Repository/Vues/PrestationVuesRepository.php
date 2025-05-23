<?php

namespace App\Repository\Vues;

use App\Entity\Vues\PrestationVues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestationVues>
 *
 * @method PrestationVues|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrestationVues|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrestationVues[]    findAll()
 * @method PrestationVues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrestationVuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestationVues::class);
    }

//    /**
//     * @return PrestationVues[] Returns an array of PrestationVues objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PrestationVues
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
